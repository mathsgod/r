<?

namespace R;

class Model extends ORM\Model
{
    public static $db;
    public static function __db()
    {
        return self::$db;
    }
}
