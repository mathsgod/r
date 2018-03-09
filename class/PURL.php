<?php
namespace R;
class PURL {
    private $url;
    private $purl;
    public function __construct($url) {
        $this->url = $url;
        $this->purl = parse_url($url);
    }

    public function segment($segment = null) {
        $path = $this->purl["path"];
        if ($path[0] == "/")$path = substr($path, 1);
        $p = explode("/", $path);
        if ($segment === null) {
            return $p;
        }
        return $p[$segment];
    }

    public function path() {
        return $this->purl["path"];
    }

    public function __toString() {
        return $this->url;
    }

    public function param($name) {
        $result = [];
        parse_str($this->purl["query"], $result);

        if ($name) {
            return $result[$name];
        }
        return $result;
    }
}

?>