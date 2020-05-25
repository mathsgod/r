<?php

namespace R;

class Model extends ORM\Model
{
    /**
     * @var \R\DB\Schema
     */
    public static $db;
    public static function __db()
    {
        return self::$db;
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
                            $this->$key = implode(",", array_filter($rs[$key], function ($o) {
                                return $o !== "";
                            }));
                        } else {
                            $this->$key = $rs[$key];
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function save()
    {
        $new_record = !$this->_id();

        $vars = get_object_vars($this);
        if ($new_record) { // Insert
            if (array_key_exists("created_time", $vars)) {
                $this->created_time = date("Y-m-d H:i:s");
            }
        } else { // Update
            if (array_key_exists("updated_time", $vars)) {
                $this->updated_time = date("Y-m-d H:i:s");
            }
        }

        return parent::save();
    }
}
