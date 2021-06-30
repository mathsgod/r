<?php

namespace R;

use PHP\Psr7\JsonStream;
use PHP\Psr7\StringStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Page
{
    public $app;
    public $file;
    public $root;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    public function __construct(App $app)
    {
        $class = get_called_class();
        $this->app = $app;
        $this->file = $app->loader->findFile($class);
        $this->root = $app->root;
    }

    public function write($element)
    {
        $this->response->getBody()->write($element);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->request = $request;
        $this->response = $response;

        $target = $request->getRequestTarget();
        $r_class = new \ReflectionClass(get_called_class());
        $params = $request->getQueryParams();

        if ($r_class->hasMethod($target)) {
            try {
                $data = [];
                if ($target == "__invoke" || $target == "write") {
                    return $this->response->withBody(new StringStream("cannot use method: $target"));
                };

                foreach ($r_class->getMethod($target)->getParameters() as $param) {
                    $name = $param->name;

                    if (isset($params[$name])) {
                        $data[] = $params[$name];
                    } else {
                        if ($param->isDefaultValueAvailable()) {
                            $data[] = $param->getDefaultValue();
                        } else {
                            $data[] = null;
                        }
                    }
                }
                $ret = call_user_func_array([$this, $target], $data);
            } catch (\ReflectionException $e) {
                $ret = call_user_func_array([$this, $target], $params);
            }
        } else {
            $ret = null;
        }


        if ($ret !== null) {
            $this->response =  $this->response
                ->withHeader("Content-Type", "application/json; charset=UTF-8")
                ->withBody(new JsonStream($ret));
        }

        return $this->response;
    }
}
