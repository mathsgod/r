<?php

namespace R;

use Composer\Autoload\ClassLoader;
use Psr\Http\Message\RequestInterface;

class Router
{
    public $route = [];
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function add($method, $path, $params)
    {
        $this->route[] = ["method" => $method, "path" => $path, "params" => $params];
    }

    public function addRoute(callable $callable)
    {
        return $this->route[] = $callable;
    }

    public function getRoute(RequestInterface $request)
    {
        $path = $request->getUri()->getPath();

        $path = array_filter(explode("/", $path), function ($o) {
            return !is_numeric($o);
        });
        $path = implode("/", $path);

        $method = $request->getMethod();
        foreach ($this->route as $route) {
            if (is_callable($route)) {
                if ($r = $route($request, $this->app)) {
                    return $r;
                }
            } elseif ($method == $route["method"] && $path == $route["path"]) {
                $r = new Route($request, $this->app);

                $r->path = $path;
                $r->uri = (string) $request->getURI();
                $r->class = $route["params"]["class"];
                $r->method = strtolower($route["method"]);
                $r->action = basename($route["path"]);
                parse_str($request->getURI()->getQuery(), $r->query);
                $r->file = $this->root . "/" . $route["params"]["file"];

                $this->loader->addClassMap([
                    $r->class => $r->file
                ]);

                return $r;
            }
        }
        return new Route($request, $this->app);
    }
}
