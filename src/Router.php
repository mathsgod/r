<?

namespace R;

use R\Psr7\ServerRequest;

class Router
{
    public $route = [];

    public function add($method, $path, $params)
    {
        $this->route[] = ["method" => $method, "path" => $path, "params" => $params];
    }

    public function addRoute(callable $callable)
    {
        return $this->route[] = $callable;
    }

    public function getRoute(ServerRequest $request, $loader)
    {
        $document_root = $request->getServerParams()["DOCUMENT_ROOT"];
        $base = $request->getUri()->getBasePath();
        $path = $request->getUri()->getPath();

        $path = array_filter(explode("/", $path), function ($o) {
            return !is_numeric($o);
        });
        $path = implode("/", $path);

        $method = $request->getMethod();
        foreach ($this->route as $route) {
            if (is_callable($route)) {
                if ($r = $route($request, $loader)) {
                    return $r;
                }
            } elseif ($method == $route["method"] && $path == $route["path"]) {
                $r = new Route($request, $loader);

                $r->path = $path;
                $r->uri = (string) $request->getURI();
                $r->class = $route["params"]["class"];
                $r->method = strtolower($route["method"]);
                $r->action = basename($route["path"]);
                parse_str($request->getURI()->getQuery(), $r->query);
                $r->file = $document_root . $base . $route["params"]["file"];

                $loader->addClassMap([
                    $r->class => $r->file
                ]);

                return $r;
            }
        }
        return new Route($request, $loader);
    }
}
