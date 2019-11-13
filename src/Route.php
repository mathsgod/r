<?php

namespace R;

use Psr\Http\Message\RequestInterface;

class Route
{
    public $uri;
    public $path;
    public $class;
    public $action;
    public $method;
    public $no_index;
    public $ids = [];
    public $id;
    public $type;

    public function __construct(RequestInterface $request, $loader)
    {
        if (!$request) {
            return;
        }

        $uri = $request->getUri();
        $this->uri = (string) $uri;
        $this->path = $uri->getPath();

        $this->method = strtolower($request->getMethod());
        parse_str($uri->getQuery(), $this->query);

        // skip id
        $t = [];
        foreach (explode("/", $this->path) as $q) {
            $q = trim($q);
            if (is_numeric($q)) {
                $this->ids[] = $q;
                if (!$this->id) {
                    $this->id = $q;
                }
                continue;
            }
            if ($q) {
                $t[] = $q;
            }
        }

        $this->path = implode("/", $t);

        if ($this->path == "") {
            $this->path = "index";
        }

        if (substr($this->path, -1) == "/") {
            $this->no_index = true;
            $this->path .= "index";
        }

        $this->psr0($request, $loader);


        if (file_exists($this->file)) {
            require_once($this->file);
        }

        if (class_exists($this->class, false)) {
            $loader->addClassMap([$this->class => $this->file]);
            return;
        }

        $class = str_replace("-", "_", $this->class);
        $class = str_replace("\\", "_", $class);

        if (class_exists($class, false)) {
            $this->class = $class;
            $loader->addClassMap([$class => $this->file]);
            return;
        }
    }
    public function psr0($request, $loader)
    {
        $qs = explode("/", $this->path);
        $method = strtolower($request->getMethod());
        $root = $request->getServerParams()["DOCUMENT_ROOT"];
        $basePath = $request->getURI()->getBasePath();

        if (!$this->no_index) {
            $path = implode("/", $qs);
            if (file_exists($file = $root . $basePath . "/pages/" . $path . "/index.php")) {
                $this->file = $file;
                $this->path = implode("/", $qs) . "/index";
                $this->class = "_" . implode("_", $qs) . "_index";
                //$this->action= "_".implode("_", $qs)."_index";
                $this->method = $method;
                return;
            }
        }


        while (count($qs)) {
            $path = implode("/", $qs);

            if (file_exists($file = $root . $basePath . "/pages/" . $path . ".php")) {
                $this->file = $file;
                $this->path = $path;
                $this->class = "_" . implode("_", $qs);
                $this->action = array_pop($qs);
                $this->method = $method;
                break;
            }
            $method = array_pop($qs);
        }

        if (!$this->class) { //fall back to index
            if (file_exists($file = $root . $basePath . "/pages/index.php")) {
                $this->file = $file;
                $path = implode("/", $qs);
                $this->class = "_index";
                $this->action = array_pop($qs);
                $this->method = $method;
            }
        }
    }

    public function psr4($request, $loader)
    {
        $qs = explode("/", $this->path);

        $method = strtolower($request->getMethod());

        if (!$this->no_index) {
            $class = implode("\\", $qs) . "\index";
            if ($file = $loader->findFile($class)) {
                $this->file = $file;
                $this->path = implode("/", $qs) . "/index";
                $this->class = $class;
                $this->action = "index";
                $this->method = $method;
                $this->type = "psr4";
                return;
            }
        }

        while (count($qs)) {
            //try index
            $class = implode("\\", $qs) . "\\index";
            if ($file = $loader->findFile($class)) {
                $this->file = $file;
                $this->path = implode("/", $qs) . "/index";
                $this->class = $class;
                $this->action = "index";
                $this->method = $method;
                $this->type = "psr4";
                break;
            }

            $this->action = $qs[count($qs) - 1];

            $class = implode("\\", $qs);

            if ($file = $loader->findFile($class)) {
                $this->file = $file;
                $this->path = implode("/", $qs);
                $this->class = $class;
                $this->method = $method;
                $this->type = "psr4";
                break;
            }
            $method = array_pop($qs);
        }

        if (!$this->class) {
            $root = $request->getServerParams()["DOCUMENT_ROOT"];
            $this->file = $root . "/pages/index.php";
            $this->path = "index";
            $this->class = "index";
            $this->method = $this->action;
        }
    }


    public function uri()
    {
        return new PURL($this->path);
    }
}
