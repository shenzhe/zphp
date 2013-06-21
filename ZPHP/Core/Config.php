<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * config配置处理
 */

namespace ZPHP\Core;
use ZPHP\Common\Dir;

class Config
{

    private static $config;

    public static function load($configPath)
    {
        $files = Dir::tree($configPath, "/.php$/");
        $config = array();
        if (!empty($files)) {
            foreach ($files as $file) {
                $config += include "{$file}";
            }
        }
        self::$config = $config;
        return $config;
    }

    public static function get($key, $default = null, $throw=false)
    {
        $result =  isset(self::$config[$key]) ? self::$config[$key] : $default;
        if($throw && empty($result)) {
            throw new \Exception("{key} config empty");
        }
        return $result;
    }

    public static function all()
    {
        return self::$config;
    }
}
