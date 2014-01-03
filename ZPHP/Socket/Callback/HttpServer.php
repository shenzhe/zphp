<?php

namespace ZPHP\Socket\Callback;

use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Protocol;
use ZPHP\Core;
use \HttpParser;
use ZPHP\Conn\Factory as ZConn;


class HttpServer implements ICallback
{

    private $_cache;
    public function onStart()
    {
        //echo 'server start, swoole version: ' . SWOOLE_VERSION . PHP_EOL;
    }

    public function onConnect()
    {
        /*
        $params = func_get_args();
        $fd = $params[1];
        echo "{$fd} connected".PHP_EOL;
        */
    }

    /**
     * 支持get方式：url示例： http://host:port/?a=ctrl&m=method&key1=>val1&key2=val2
     */
    public function onReceive()
    {
        $params = func_get_args();
        $_data = $params[3];
        $serv = $params[0];
        $fd = $params[1];
        $parser = new HttpParser();
        $buffer = $this->cache->getBuffer($fd);
        $nparsed = (int) $this->cache->getBuffer($fd, 'nparsed');
        $buffer .= $_data;
        $nparsed = $parser->execute($buffer, $nparsed);
        if($parser->hasError()) {
            $serv->close($fd, $params[2]);
            $this->clearBuff($fd);
        } elseif ($parser->isFinished()) {
            $this->clearBuff($fd);
            var_dump($parser->getEnvironment());
            $this->sendOne($serv, $fd, 'hello world~');
        } else {
            $buffer = $this->cache->setBuffer($fd, $buffer);
            $nparsed = (int) $this->cache->setBuffer($fd, $nparsed, 'nparsed');
        }
    }

    private function clearBuff($fd)
    {
        $this->cache->delBuffer($fd, 'nparsed');
        $this->cache->delBuffer($fd);
        return true;
    }

    public function onClose()
    {
        /*
        $params = func_get_args();
        $fd = $params[1];
        echo "{$fd} closed".PHP_EOL;
        */
        $this->cache->delBuff($params[1]);
    }

    public function onShutdown()
    {
        //echo "server shut dowm\n";
        $this->cache->clear();
    }

    /**
     * @param $serv
     * @param $fd
     * @param $data
     * @return bool
     *  支持返回json数据
     */
    public function sendOne($serv, $fd, $data)
    {
        $response = join(
            "\r\n",
            array(
                'HTTP/1.1 200 OK',
                'Content-Type: text/html',
                'Content-Length: '.strlen($data),
                '',
                $data));
        $serv->send($fd, $response);
        $serv->close($fd);
    }


    private function _route($data)
    {
        try {
            $server = Protocol\Factory::getInstance(ZConfig::getField('socket', 'protocol', 'Rpc'));
            $server->parse($data);
            $result =  Core\Route::route($server);
            return $result;
        } catch (\Exception $e) {
            //print_r($e);
            return null;
        }
    }


    public function onWorkerStart()
    {
        $params = func_get_args();
        $worker_id = $params[1];
        echo "WorkerStart[$worker_id]|pid=" . posix_getpid() . ".\n";
        $this->cache = ZConn::getInstance(ZConfig::get('cache'));

    }

    public function onWorkerStop()
    {
        $params = func_get_args();
        $worker_id = $params[1];
    }
}
