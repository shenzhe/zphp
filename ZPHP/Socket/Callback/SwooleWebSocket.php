<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Core;


abstract class SwooleWebSocket extends SwooleHttp
{
    public function onHandShake($request, $response)
    {

    }

    public function onOpen($server, $request)
    {

    }

    public function onRequest($request, $reponse)
    {

    }

    abstract public function onMessage($server, $frame);
}
