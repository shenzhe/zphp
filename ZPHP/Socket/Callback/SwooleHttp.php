<?php


namespace ZPHP\Socket\Callback;

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
    }

    public function doRequest($request, $response)
    {
        Protocol\Request::setRequest($request);
        Protocol\Response::setResponse($response);
        $this->onRequest($request, $response);
        Protocol\Request::setRequest(null);
        Protocol\Response::setResponse(null);
    }

    abstract public function onRequest($request, $response);
}
