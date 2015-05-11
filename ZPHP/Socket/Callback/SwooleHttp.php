<?php

/**
 *  依赖于 httpparser扩展和yac扩展
 *  git地址：https://github.com/matyhtf/php-webserver/tree/master/ext
 *  git地址：https://github.com/laruence/yac
 */

namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol;
use ZPHP\Core;
use \HttpParser;
use ZPHP\Conn\Factory as ZCache;


class SwooleHttp
{

    private $cache;
    private $_route;
    public $serv;
    private $mimes = array();

    public function onStart()
    {
        $config = ZConfig::getField('cache', 'locale');
        $this->cache = ZCache::getInstance($config['adapter'], $config);
    }

    public function onConnect()
    {
        $params = func_get_args();
        $fd = $params[1];
        //echo "{$fd} connected".PHP_EOL;
        
    }

    /**
     *  请求发起
     */
    public function onMessage()
    {
        $params = func_get_args();
        $_data = $params[3];
        $serv = $params[0];
        $fd = $params[1];
    }

    public function onClose()
    {
        $params = func_get_args();
        $this->cache->delBuff($params[1]);
    }

    public function onShutdown()
    {
        //echo "server shut dowm\n";
        if($this->cache) {
            $this->cache->clear();
        }
    }


    public function onWorkerStart()
    {
        $params = func_get_args();

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
}
