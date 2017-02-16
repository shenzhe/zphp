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
    private static $lastModifyTime = [];
    private static $configPath;
    private static $reloadPath;

    public static function load($configPath)
    {
        $files = Dir::tree($configPath, "/.php$/");
        $config = array();
        if (!empty($files)) {
            foreach ($files as $file) {
                if (Request::isLongServer() && function_exists("opcache_invalidate")) {
                    \opcache_invalidate($file);
                }
                $config += include "{$file}";
            }
        }
        self::$config = $config;
        self::$configPath = $configPath;
        if (!empty(self::$config['project']['auto_reload'])
            && !empty(self::$config['project']['reload_path'])
        ) {
            self::mergePath(self::$config['project']['reload_path']);
        }
        return self::$config;
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

    public static function mergePath($path)
    {
        $files = Dir::tree($path, "/.php$/");
        if (!empty($files)) {
            $config = array();
            foreach ($files as $file) {
                if (Request::isLongServer() && function_exists("opcache_invalidate")) {
                    \opcache_invalidate($file);
                }
                $config += include "{$file}";
            }
            self::$config = array_merge(self::$config, $config);
        }
        if (Request::isLongServer()) {
            self::$reloadPath[$path] = $path;
            self::$nextCheckTime = time() + empty($config['project']['config_check_time']) ? 5 : $config['project']['config_check_time'];
            self::$lastModifyTime[$path] = \filectime($path);
        }
    }

    public static function mergeFile($file)
    {
        $tmp = include "{$file}";
        if (empty($tmp)) {
            return false;
        }
        self::$config = array_merge(self::$config, $tmp);
        return true;
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
        self::checkTime();
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
            if (self::$nextCheckTime < time() && !empty(self::$reloadPath)) {
                foreach (self::$reloadPath as $path) {
                    if (!is_dir($path)) {
                        continue;
                    }
                    \clearstatcache($path);
                    if (self::$lastModifyTime[$path] < \filectime($path)) {
                        self::mergePath($path);
                    }
                }
            }
        }
        return;
    }
}
