<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Core;
use ZPHP\Protocol;


abstract class SwooleHttp extends Swoole
{

    public function onReceive()
    {
        throw new \Exception('http server must use onRequest');
    }

    abstract public function onRequest($request, $reponse);
}
