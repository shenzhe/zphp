<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *
 */
namespace ZPHP\View;

use ZPHP\Core\Factory as CFactory;

class Factory
{
    private static $_map = [
        'Amf' => 1,
        'Ant' => 1,
        'Json' => 1,
        'Php' => 1,
        'Str' => 1,
        'String' => 1,
        'Xml' => 1,
        'Zpack' => 1,
        'Zrpack' => 1,
    ];

    public static function getInstance($adapter = 'Json')
    {
        $adapter = ucfirst(strtolower($adapter));
        if ('String' == $adapter) {
            $adapter = 'Str';
        }
        if (isset(self::$_map[$adapter])) {
            $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        } else {
            $className = $adapter;
        }
        return CFactory::getInstance($className);
    }
}