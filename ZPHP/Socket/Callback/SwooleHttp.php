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

    public function onWorkerStart($server, $workerId)
    {
        parent::onWorkerStart($server, $workerId);
        Protocol\Request::setHttpServer(1);
        Protocol\Request::setSocket($server);
    }

    public function doRequest($request, $response)
    {
        Protocol\Request::setRequest($request);
        Protocol\Response::setResponse($response);
        $this->onRequest($request, $response);
    }

    abstract public function onRequest($request, $response);
}
