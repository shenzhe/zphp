<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Core;


abstract class SwooleWebSocket extends SwooleHttp
{
    public function onRequest($request, $response)
    {
        $response->end('hello zphp');
    }

    abstract public function onMessage($server, $frame);
}
