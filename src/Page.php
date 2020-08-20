<?php

namespace R;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use R\Psr7\Stream;
use R\Psr7\JSONStream;

abstract class Page
{
    public $app;
    public $file;
    public $root;

    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface
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

    public function __invoke(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->request = $request;
        $this->response = $response;

        $method = $request->getMethod();

        $r_class = new \ReflectionClass(get_called_class());


        if ($request instanceof ServerRequestInterface) {
            $params = $request->getQueryParams();
        } else {
            $params = $request->getUri()->getQuery();
        }

        try {
            $data = [];
            if ($method == "__invoke" || $method == "write") {
                return $this->response->withBody(new Stream("cannot use method: $method"));
            };

            foreach ($r_class->getMethod($method)->getParameters() as $param) {
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
            $ret = call_user_func_array([$this, $method], $data);
        } catch (\ReflectionException $e) {
            $ret = call_user_func_array([$this, $method], $params);
        }

        if ($ret !== null) {
            $this->response = $this->response->withHeader("Content-Type", "application/json; charset=UTF-8");
            $body = new JSONStream();
            $body->write($ret);
            return $this->response->withBody($body);
        }

        return $this->response;
    }
}
