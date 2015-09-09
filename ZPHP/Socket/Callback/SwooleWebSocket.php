<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Conn\Adapter\Swoole;
use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Core;
use ZPHP\View\Factory as ZView;


abstract class SwooleWebSocket extends SwooleHttp
{

    public function onOpen($server, $request)
    {

    }

    public function onRequest($request, $reponse)
    {

    }

    abstract public function onMessage($server, $frame);
}
