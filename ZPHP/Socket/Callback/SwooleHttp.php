<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Core;
use ZPHP\Common\Utils;
use ZPHP\Protocol;


class SwooleHttp implements ICallback
{

    protected $protocol;
    protected $protocolName = 'Http';

    protected $serv;

    public function onStart()
    {
        if(!$this->protocol) {
            $this->protocol = Protocol\Factory::getInstance(ZConfig::getField('project', 'protocol', $this->protocolName));
        }
    }

    public function onConnect()
    {
    }

    public function onReceive()
    {
        throw new \Exception('swoole http is onRequest');
    }

    /**
     *  请求发起
     */
    public function onRequest($request, $response)
    {
        Utils::$response = $response;
        $this->protocol->parse($_REQUEST);
        $result =  Core\Route::route($this->protocol);
        return $response->end($result);
    }

    public function onClose()
    {
        $params = func_get_args();
        $this->cache->delBuff($params[1]);
    }

    public function onShutdown()
    {

    }


    public function onWorkerStart()
    {
    }

    public function onWorkerStop()
    {
    }
    
    public function onTask()
    {
        
    }
    
    public function onFinish()
    {
        
    }

    public function setServer($serv)
    {
        $this->serv = $serv;
    }
}
