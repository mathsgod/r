<?php

namespace R;

use Composer\Autoload\ClassLoader;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Exception;
use PDOException;
use PHP\Psr7\JsonStream;
use PHP\Psr7\StringStream;
use PHP\Psr7\Response;
use PHP\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

class App implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    public $root;
    public $document_root;
    public $base_path;
    public $request;
    public $config = [];
    public $loader;
    public $router;

    /**
     * @var \R\DB\Schema
     */
    public $db;

    public function __construct(string $root = null, ClassLoader $loader = null, LoggerInterface $logger = null)
    {
        $this->loader = $loader ?? new ClassLoader();
        if ($logger) $this->setLogger($logger);

        $this->root = $root ?? getcwd();

        $this->loader->addPsr4("", $this->root . DIRECTORY_SEPARATOR . "class", true);
        $this->loader->register();

        $this->request = new ServerRequest;

        $server = $this->request->getServerParams();
        //base path
        $this->base_path = dirname($server["SCRIPT_NAME"]);
        if (substr($this->base_path, -1) != "/") {
            $this->base_path .= "/";
        }

        $this->document_root = substr($this->root."/", 0, -strlen($this->base_path));

        if (is_readable($ini = $this->root . DIRECTORY_SEPARATOR . "config.ini")) {
            $this->config = parse_ini_file($ini, true);
        }

        if (is_readable($ini = $this->document_root . DIRECTORY_SEPARATOR . "config.ini")) {
            foreach (parse_ini_file($ini, true) as $k => $v) {
                $this->config[$k] = array_merge($this->config[$k] ?? [], $v);
            }
        }

        if ($this->config["r"]["document_root"]) {
            $this->document_root = $this->config["r"]["document_root"];
        }

        if ($db = $this->config["database"]) {
            if (empty($db["charset"])) {
                $db["charset"] = "utf8mb4";
            }

            if (!$db["port"]) {
                $db["port"] = 3306;
            }

            try {
                $this->db = new \R\DB\Schema($db["database"], $db["hostname"], $db["username"], $db["password"], $db["charset"], $db["port"]);
                if ($logger) $this->db->setLogger($logger);
            } catch (PDOException $e) {
                if ($this->logger) {
                    $this->logger->error($e->getMessage());
                }
                throw new Exception("SQLSTATE[HY000] [1045] Access denied");
            }

            if (isset($db["ERRMODE"])) {
                $this->db->setAttribute(\PDO::ATTR_ERRMODE, $db["ERRMODE"]);
            }

            Model::$db = $this->db;
        }
        $this->router = new Router($this);
    }

    public function run()
    {
        //* Create $_POST
        if ($this->request->getMethod() == "POST") {
            if (strpos($this->request->getHeaderLine("Content-Type"), "application/json") !== false) {
                $_POST = $this->request->getParsedBody();
            }
        }

        //* Request Change
        $uri = $this->request->getUri();
        $path = substr($uri->getPath(), strlen($this->base_path));
        $uri = $uri->withPath($path);
        $this->request = $this->request->withUri($uri);


        $route = $this->router->getRoute($this->request);

        $request = $this->request->withRequestTarget($route->method);

        if ($class = $route->class) {
            $page = new $class($this);
            $response = new Response(200);

            if ($this->logger) $this->logger->debug($class . " invoke start");

            try {
                $response = $page($request, $response);
                if (!$response instanceof ResponseInterface) {
                    throw new RuntimeException("page invoke must be ResponseInterface");
                    return;
                }
            } catch (Exception $e) {
                if ($request->getHeader("Accept")[0] == "application/json") {
                    $response = $response->withHeader("Content-Type", "application/json; charset=UTF-8");
                    $ret = ["error" => ["message" => $e->getMessage()]];
                    if ($code = $e->getCode()) {
                        $ret["error"]["code"] = $code;
                    }
                    $response = $response->withBody(new JsonStream($ret));
                } else {
                    $response = $response
                        ->withHeader("Content-Type", "text/html; charset=UTF-8")
                        ->withBody(new StringStream($e->getMessage()));
                }
            }

            if ($this->logger) $this->logger->debug($class . " invoke end");

            if (($statusCode = $response->getStatusCode()) != 200) {
                header($request->getServerParams()["SERVER_PROTOCOL"] . " " . $statusCode . " " . $response->getReasonPhrase());
            }

            foreach ($response->getHeaders() as $name => $values) {
                header($name . ": " . $response->getHeaderLine($name));
            }

            fwrite(fopen("php://output", "w"), $response->getBody());
        }
    }
}
