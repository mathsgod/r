<?php

namespace R;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use R\Psr7\Stream;
use R\Psr7\JSONStream;

class Page
{
    protected $request;
    protected $response;
    protected $logger;

    public function __construct(App $app)
    {
        $class = get_called_class();
        $this->app = $app;
        $this->file = $app->loader->findFile($class);
        $this->root = $app->root;
        $this->logger = $app->logger;
    }

    public function write($element)
    {
        $this->response->getBody()->write($element);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
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
            $ret = call_user_func_array([$this, $method], $data);
        } catch (\ReflectionException $e) {
            $ret = call_user_func_array([$this, $method], $params);
        }

        if ($ret !== null) {
            $this->response = $this->response->withHeader("Content-Type", "application/json; charset=UTF-8");
            $body = new JSONStream($ret);
            $this->response = $this->response->withBody($body);
        }

        return $this->response;
    }
}
