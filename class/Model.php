<?php

namespace R;

use R\DB\RSList;

abstract class Model extends \R\DB\Model
{
    public static $__db;

    public static function __db()
    {
        return self::$__db;
    }

    public static function distinct($query, $where = null, $order = null)
    {
        $rs = self::__from()->where($where)->orderby($order)->select("distinct(`$query`)");
        $r = new RSList($rs);
        return $r->map(function ($o) {
            $a = array_values($o);
            return $a[0];
        });
    }

    public static function Scalar($query, $where = null)
    {
        return self::__from()->where($where)->select($query)->fetchColumn(0);
    }

    public static function Count($where = null)
    {
        return self::_count($where);
    }

    public static function first($where = null, $order = null)
    {
        return parent::First($where, $order);
    }

    public function id()
    {
        if ($this->_key) {
            $key = $this->_key;
        } else {
            $key = static::_key();
        }
        return $this->$key;
    }

    public function __get($name)
    {
        if ($name == "") return "";

        if (in_array($name, get_object_vars($this))) {
            return $this->$name;
        } else {
            $class = get_called_class();
            throw new \Exception("Error: try to getting property: {$class}::{$name}", null);
        }
    }

    public function bind($rs)
    {
        foreach (get_object_vars($this) as $key => $val) {
            if (is_object($rs)) {
                if (isset($rs->$key)) {
                    if ($key[0] != "_") {
                        if (is_array($rs->$key)) {
                            $this->$key = implode(",", $rs->$key);
                        } else {
                            $this->$key = $rs->$key;
                        }
                    }
                }
            } else {
                if (array_key_exists($key, $rs)) {
                    if ($key[0] != "_") {
                        if (is_array($rs[$key])) {
                            $this->$key = implode(",", $rs[$key]);
                        } else {
                            $this->$key = $rs[$key];
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function __call($class_name, $args)
    {
        $ro = new \ReflectionObject($this);

        $namespace = $ro->getNamespaceName();
        if ($namespace == "") {
            $class = $class_name;
        } else {
            $class = $namespace . "\\" . $class_name;
            if (!class_exists($class)) {
                $class = $class_name;
            }
        }

        if (!class_exists($class)) {
            throw new \Exception($class . " class not found");
        }

        $key = forward_static_call(array($class, "_key"));

        if (in_array($key, array_keys(get_object_vars($this)))) {
            $id = $this->$key;
            if (!$id) return null;
            return new $class($this->$key);
        }

        if (!$this->id()) {
            return new DataList();
        }
        $key = static::_key();
        if (is_array($args[0])) {
            $args[0][] = "{$key}={$this->id()}";
        } else {
            if ($args[0] != "") {
                $args[0] = "({$key}={$this->id()}) AND ($args[0])";
            } else {
                $args[0] = "{$key}={$this->id()}";
            }
        }
        // if($class_name=="UserLog"){
        // print_r($args);
        // echo $class_name;
        // outp(\App\UserLog::find(["user_id=1"]));
        // }

        return forward_static_call_array(array($class, "find"), $args);
    }

    public function _distinct($class, $query, $where = null, $order = null)
    {
        $id = $this->id();
        $key = static::_key();

        $f = from($class);
        $f = $f->where($key . "=" . $id);
        $f = $f->where($where);
        $f = $f->orderby($order);

        return $f->select("distinct(`$query`)")->map(function ($o) {
            $a = array_values($o);
            return $a[0];
        });
    }

    public function _size($class, $where = null)
    {
        $rc = new \ReflectionClass(get_called_class());
        $namespace = $rc->getNamespaceName();

        $id = $this->id();
        if (!$id) return 0;
        $key = static::_key();

        if ($namespace != "") {
            $class="\\" . $namespace . "\\" . $class;
        } else {
            $class="\\$class";
        }

        $f=$class::_table()->find();
        $f->where($key . "=" . $id);
        $f->where($where);
        return $f->count();
    }

    public function _delete($class, $where = null)
    {
        $id = $this->id();
        $key = static::_key();

        $rc = new \ReflectionClass(get_called_class());
        $namespace = $rc->getNamespaceName();
        if ($namespace != "") {
            $class="\\" . $namespace . "\\" . $class;
        } else {
            $class="\\$class";
        }
        $f=$class::_table()->delete();
        $f->where($key . "=" . intval($id));
        $f->where($where);
        return $f->execute();
    }

    public function _scalar($class, $query, $where)
    {
        $id = $this->id();
        $key = static::_key();
        $rc = new \ReflectionClass(get_called_class());
        $namespace = $rc->getNamespaceName();
        if ($namespace != "") {
            $class="\\" . $namespace . "\\" . $class;
        } else {
            $class="\\$class";
        }

        $f=$class::_table()->select($query);
        $f->where($key . "=" . intval($id));
        $f->where($where);
        return $r->execute()->fetchColumn(0);
    }
}