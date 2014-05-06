<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Session;
use ZPHP\Core\Factory as CFactory,
    ZPHP\Core\Config as ZConfig;

class Factory
{
    private static $isStart=false;
    public static function getInstance($adapter = 'Redis', $config=null)
    {
        if(empty($config)) {
            $config = ZConfig::get('session');
            if(!empty($config['adapter'])) {
                $adapter = $config['adapter'];
            }
        }
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }

    public static function start($sessionType = '', $config = '')
    {
        if(false === self::$isStart) {
            if(empty($config)) {
                $config = ZConfig::get('session');
                if(!empty($config['adapter'])) {
                    $sessionType = $config['adapter'];
                }
            }
            if (!empty($sessionType)) {
                $handler = self::getInstance($sessionType, $config);
                \session_set_save_handler(
                    array($handler, 'open'),
                    array($handler, 'close'),
                    array($handler, 'read'),
                    array($handler, 'write'),
                    array($handler, 'destroy'),
                    array($handler, 'gc')
                );
            }
            \session_name(ZConfig::getField('project', 'session_name', 'ZPHPSESSID'));
            \session_start();
            self::$isStart = true;
        }
    }
}