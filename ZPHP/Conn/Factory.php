<?php

namespace ZPHP\Conn;

/**
 * connect处理工厂
 *
 */
class Factory
{

    private static $cache = array();

    public static function getInstance($type = "Redis", $config)
    {
        $cacheType = __NAMESPACE__.'\\Adapter\\' . $type;
        if (!isset(self::$cache[$type])) {
            self::$cache[$type] = new $cacheType($config);
        }
        return self::$cache[$type];
    }

}
