<?php
namespace R;
class RSList extends DataList {
    public $class = null;
    public $rs = null;

    public function __construct($rs = null, $class = null) {
        $this->rs = $rs;

        if ($class && $rs) {
            $this->class = $class;

        	$rs->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, $class, []);
        }

        parent::__construct(iterator_to_array($rs));
    }
}