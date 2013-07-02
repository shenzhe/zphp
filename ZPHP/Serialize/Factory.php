<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */
namespace ZPHP\Serialize;
use ZPHP\Core\Factory as CFactory;

class Factory
{
    public static function getInstance($adapter = 'Php')
    {
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className);
    }

    public static function serialize($adapter = 'Php', $data)
    {
        $class = self::getInstance($adapter);
        return $class->serialize($data);
    }

    public static function unserialize($adapter = 'Php', $data)
    {
        $class = self::getInstance($adapter);
        return $class->unserialize($data);
    }
}
