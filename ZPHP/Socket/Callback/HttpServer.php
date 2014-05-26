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


abstract class HttpServer implements ICallback
{

    private $cache;
    private $_route;
    public $serv;
    private $mimes = array();

    abstract public function onSend($fd, $data);

    public function onStart()
    {
        echo 'server start, swoole version: ' . SWOOLE_VERSION . PHP_EOL;
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
    public function onReceive()
    {
        $params = func_get_args();
        $_data = $params[3];
        $serv = $params[0];
        $fd = $params[1];
        $parser = new HttpParser();
        $buffer = $this->cache->getBuff($fd);
        $nparsed = (int) $this->cache->getBuff($fd, 'nparsed');
        $buffer .= $_data;
        $nparsed = $parser->execute($buffer, $nparsed);
        if($parser->hasError()) {
            $serv->close($fd, $params[2]);
            $this->_clearBuff($fd);
        } elseif ($parser->isFinished()) {
            $this->_clearBuff($fd);
            $this->onSend($fd, $this->_getData($parser->getEnvironment()));
        } else {
            $buffer = $this->cache->setBuff($fd, $buffer);
            $nparsed = (int) $this->cache->setBuff($fd, $nparsed, 'nparsed');
        }
    }

    private function _getData($data)
    {
        $_SERVER = $data;
        switch ($data['REQUEST_METHOD']) {
            case 'POST':
                parse_str($data['QUERY_STRING'].'&'.$data['REQUEST_BODY'], $param);
                return $param;
                break;
            case 'PUT':
                break;
            case 'DELETE':
                break;
            default:   //GET
                if(empty($data['QUERY_STRING'])) {
                    return array();
                }

                parse_str($data['QUERY_STRING'], $param);
                return $param;
                break;
        }
    }

    private function _clearBuff($fd)
    {
        $this->cache->delBuff($fd, 'nparsed');
        $this->cache->delBuff($fd);
        return true;
    }

    public function onClose()
    {
        $params = func_get_args();
        //$fd = $params[1];
        //echo "{$fd} closed".PHP_EOL;   
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
        //$worker_id = $params[1];
        //echo "WorkerStart[$worker_id]|pid=" . posix_getpid() . ".\n";
        $config = ZConfig::getField('cache', 'locale');
        $this->cache = ZCache::getInstance($config['adapter'], $config);
        $this->serv = $params[0];
        if(is_file(__DIR__.DS.'Mimes.php')) {
            $mimes = include(__DIR__.DS.'Mimes.php');
            $this->mimes = array_flip($mimes);
        }

    }

    public function onWorkerStop()
    {
        /*
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStop[$worker_id]|pid=" . posix_getpid() . ".\n";
        */
    }
    
    public function onTask()
    {
        
    }
    
    public function onFinish()
    {
        
    }

    public function getMime($filename)
    {
        $ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
        if(isset($this->mimes[$ext])) {
            return $this->mimes[$ext];
        } else {
            return 'text/html';
        }
    }
}
