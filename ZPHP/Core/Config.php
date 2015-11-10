<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * config配置处理
 */

namespace ZPHP\Core;

use ZPHP\Common\Dir;
use ZPHP\Protocol\Request;

class Config
{

    private static $config;
    private static $nextCheckTime = 0;
    private static $lastModifyTime = 0;
    private static $configPath;

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
        if (Request::isLongServer()) {
            self::$configPath = $configPath;
            self::$nextCheckTime = time() + empty($config['project']['config_check_time']) ? 5 : $config['project']['config_check_time'];
            self::$lastModifyTime = \filectime($configPath);
        }
        return $config;
    }

    public static function loadFiles(array $files)
    {
        $config = array();
        foreach ($files as $file) {
            $config += include "{$file}";
        }
        self::$config = $config;
        return $config;
    }

    public static function get($key, $default = null, $throw = false)
    {
        self::checkTime();
        $result = isset(self::$config[$key]) ? self::$config[$key] : $default;
        if ($throw && is_null($result)) {
            throw new \Exception("{key} config empty");
        }
        return $result;
    }

    public static function set($key, $value, $set = true)
    {
        if ($set) {
            self::$config[$key] = $value;
        } else {
            if (empty(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }

        return true;
    }

    public static function getField($key, $field, $default = null, $throw = false)
    {
        $result = isset(self::$config[$key][$field]) ? self::$config[$key][$field] : $default;
        if ($throw && is_null($result)) {
            throw new \Exception("{key} config empty");
        }
        return $result;
    }

    public static function setField($key, $field, $value, $set = true)
    {
        if ($set) {
            self::$config[$key][$field] = $value;
        } else {
            if (empty(self::$config[$key][$field])) {
                self::$config[$key][$field] = $value;
            }
        }

        return true;
    }

    public static function all()
    {
        return self::$config;
    }

    public static function checkTime()
    {
        if (Request::isLongServer()) {
            if (self::$nextCheckTime < time()) {
                if (self::$lastModifyTime < \filectime(self::$configPath)) {
                    self::load(self::$configPath);
                }
            }
        }
        return;
    }
}
