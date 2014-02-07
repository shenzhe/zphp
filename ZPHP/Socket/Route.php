<?php

namespace ZPHP\Socket;
use ZPHP\Core\Factory as CFactory;

class Route
{
    public static function getInstance($adapter = 'ZPHP')
    {
        $className = __NAMESPACE__ . "\\Route\\{$adapter}";
        return CFactory::getInstance($className);
    }

}