<?php

namespace R;

use R\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use R\Psr7\Stream;
use Exception;

class App implements LoggerAwareInterface
{
    public $root;
    public $request;
    public $config = [];
    public $loader;
    public $logger;
    public $db;

    public function __construct($root, $loader = null, $logger = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->loader = $loader ? $loader : new \Composer\Autoload\ClassLoader();
        $this->request = ServerRequest::FromEnv();
        $this->router = new Router();
        $this->logger = $logger;

        $this->root = $root ? $root : getcwd();

        $this->loader->addPsr4("", $this->root . "/class");
        $this->loader->register();

        if (is_readable($ini = $this->root . "/config.ini")) {
            $this->config = parse_ini_file($ini, true);
        }

        if ($db = $this->config["database"]) {
            if (empty($db["charset"])) {
                $db["charset"] = "utf8mb4";
            }

            $port = $db["port"] ? $db["port"] : 3306;

            $this->db = new \R\DB\Schema($db["database"], $db["hostname"], $db["username"], $db["password"], $db["charset"], $port);
            if ($this->logger) {
                $this->db->setLogger($this->logger);
            }


            if (isset($db["ERRMODE"])) {
                $this->db->setAttribute(\PDO::ATTR_ERRMODE, $db["ERRMODE"]);
            }
        }
    }

    public function run()
    {
        $route = $this->router->getRoute($this->request, $this->loader);
        $request = $this->request->withAttribute("route", $route);

        if ($class = $route->class) {
            $page = new $class($this);
            $response = new Psr7\Response(200);
            $request = $request->withMethod($route->method);

            if ($this->logger) $this->logger->debug($class . " invoke start");

            try {
                $response = $page($request, $response);
            } catch (Exception $e) {
                if ($request->getHeader("Accept")[0] == "application/json") {
                    $response = $response->withHeader("Content-Type", "application/json; charset=UTF-8");
                    if ($e->getCode()) {
                        $ret = ["code" => $e->getCode(), "message" => $e->getMessage()];
                    } else {
                        $ret = ["message" => $e->getMessage()];
                    }
                    $response = $response->withBody(new Stream(json_encode($ret)));
                } else {
                    $response = $response->withHeader("Content-Type", "text/html; charset=UTF-8")
                        ->withBody(new Stream($e->getMessage()));
                }
            }

            if ($this->logger) $this->logger->debug($class . " invoke end");

            if (($statusCode = $response->getStatusCode()) != 200) {
                header($request->getServerParams()["SERVER_PROTOCOL"] . " " . $statusCode . " " . $response->getReasonPhrase());
            }

            foreach ($response->getHeaders() as $name => $values) {
                header($name . ": " . implode(", ", $values));
            }
            file_put_contents("php://output", $response->getBody()->getContents());
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
