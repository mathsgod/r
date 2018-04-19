<?php

namespace R;

use R\Psr7\Stream;
use R\Psr7\JSONStream;
use R\Psr7\Request;
use R\Psr7\Response;

class Page
{
    protected $request;
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

    public function __invoke(Request $request, Response $response)
    {
        if (!$request) {
            throw new \InvalidArgumentException("request cannot be null");
        }
        if (!$response) {
            throw new \InvalidArgumentException("response cannot be null");
        }
        $this->request = $request;
        $this->response = $response;

        $method = $request->getMethod();

        $r_class = new \ReflectionClass(get_called_class());

        $params = $this->request->getQueryParams();
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
            try {
                $ret = call_user_func_array([$this, $method], $data);
            } catch (\Exception $e) {
                if ($this->request->getHeader("Accept")[0] == "application/json") {
                    if ($e->getCode()) {
                        $ret = ["code" => $e->getCode(), "message" => $e->getMessage()];
                    } else {
                        $ret = ["message" => $e->getMessage()];
                    }
                } else {
                    return $this->response
                        ->withHeader("Content-Type", "text/html; charset=UTF-8")
                        ->withBody(new Stream($e->getMessage()));
                }
            }
        } catch (\ReflectionException $e) {
            $ret = call_user_func_array([$this, $method], $params);
        }


        if ($ret !== null) {
            $this->response->setHeader("Content-Type", "application/json; charset=UTF-8");
            $body = new JSONStream();
            $body->write($ret);
            return $this->response->withBody($body);
        }

        return $this->response;
    }
}