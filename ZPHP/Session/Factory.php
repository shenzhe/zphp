<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Session;
use ZPHP\Core\Factory as CFactory;

class Factory
{
    public static function getInstance($adapter='Redis', $config)
    {
        $className = __NAMESPACE__."\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }

    public static function start($sessionType = '', $config='') {
        if(!empty($sessionType)) {
            $handler = self::getInstance($sessionType, $config);
            session_set_save_handler(
                array($handler, 'open'),
                array($handler, 'close'),
                array($handler, 'read'),
                array($handler, 'write'),
                array($handler, 'destroy'),
                array($handler, 'gc')
            );
        }
        session_start();
    }
}