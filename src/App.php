<?

namespace R;

use Composer\Autoload\ClassLoader;
use R\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use R\Psr7\Stream;
use Exception;
use Psr\Log\LoggerAwareTrait;

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

    public function __construct(string $root = null, ClassLoader $loader = null, LoggerInterface  $logger = null)
    {
        $this->loader = $loader ?? new ClassLoader();
        $this->logger = $logger;

        $this->root = $root ?? getcwd();

        $this->loader->addPsr4("", $this->root . DIRECTORY_SEPARATOR . "class");
        $this->loader->register();

        $this->request = ServerRequest::FromEnv();
        $this->base_path = $this->request->getUri()->getBasePath();
        $this->document_root = substr($this->root, 0, -strlen($this->base_path));

        if (is_readable($ini = $this->root . DIRECTORY_SEPARATOR . "config.ini")) {
            $this->config = parse_ini_file($ini, true);
        }

        if (is_readable($ini = $this->document_root . DIRECTORY_SEPARATOR . "config.ini")) {
            foreach (parse_ini_file($ini, true) as $k => $v) {
                $this->config[$k] = array_merge($this->config[$k] ?? [], $v);
            }
        }

        if ($db = $this->config["database"]) {
            if (empty($db["charset"])) {
                $db["charset"] = "utf8mb4";
            }

            $this->db = new \R\DB\Schema($db["database"], $db["hostname"], $db["username"], $db["password"], $db["charset"], $this->logger);

            if (isset($db["ERRMODE"])) {
                $this->db->setAttribute(\PDO::ATTR_ERRMODE, $db["ERRMODE"]);
            }

            Model::$db = $this->db;
        }
        $this->router = new Router($this->root);
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
                    if ($code = $e->getCode()) {
                        $ret = ["error" => ["code" => $code, "message" => $e->getMessage()]];
                    } else {
                        $ret = ["error" => ["message" => $e->getMessage()]];
                    }
                    $response = $response->withBody(new Stream(json_encode($ret, JSON_UNESCAPED_UNICODE)));
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
            file_put_contents("php://output", (string) $response);
        }
    }
}
