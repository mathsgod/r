<?php
namespace R;

use R\Psr7\Stream;
use R\Psr7\ServerRequest;

class System
{
    public static $r;
    public $router;
    public $root;
    public $loader;
    public $config = [];
    public $db;
    public $logger;

    public static function Loader()
    {
        return self::$r->loader;
    }

    public static function ServerRequest()
    {
        return ServerRequest::FromEnv();
    }

    public function __construct($root, $loader, $logger = null)
    {
        $root = realpath($root);
        if (!$root) throw new \Exception("root cannot be empty");
        if (!$loader) throw new \Exception("loader cannot be empty");
        self::$r = $this;
        $this->root = $root;
        $this->loader = $loader;
        $this->logger = $logger;

        $loader->addPsr4("", $root . "/class");

        if (is_readable($ini = $root . "/config.ini")) {
            $this->config = parse_ini_file($ini, true);
        }
    }

    public static function Run($root, $loader, $logger = null)
    {
        echo '
please use follow code
<pre>
$app=new R\App();
$app->run();
</pre>
';
        exit();
    }

    public static function Root()
    {
        $r = self::$r;
        return $r->root;
    }

    public static function Config($name, $category)
    {

        $r = self::$r;
        if (func_num_args() == 1) {
            return $r->config[$name];
        }
        if (func_num_args() == 2) {
            return $r->config[$name][$category];
        }
        return $r->config;
    }

    public function db()
    {
        if ($this->db) {
            return $this->db;
        }

        $db = $this->config["database"];
        if (!$db["charset"]) {
            $db["charset"] = "utf8mb4";
        }

        $this->db = new \DB\PDO($db["database"], $db["hostname"], $db["username"], $db["password"], $db["charset"], $this->logger);



        if (isset($db["ERRMODE"])) {
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, $db["ERRMODE"]);
        }

        return $this->db;
    }

    public static function Router()
    {
        if (self::$r->router) {
            return self::$r->router;
        }
        return self::$r->router = new Router();
    }
}
