<?
namespace R;

class Entity
{
    public $_app;
    public function __construct($app)
    {
        $this->_app = $app;
    }

    public function __get($class)
    {
        $table = $class::_table();

        $q = new DB\Query($this->_app->db, $table);
        $q->select();
        return $q;
    }

}