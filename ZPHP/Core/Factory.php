<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Core;

class Factory
{
    private static $instances = array();
    public static function getInstance($className, $params=null)
    {
        if (isset(self::$instances[$className])) {
            return self::$instances[$className];
        }
        self::$instances[$className] = new $className($params);
        return self::$instances[$className];
    }
}
