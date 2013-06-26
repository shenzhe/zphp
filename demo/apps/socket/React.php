<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */
namespace socket;
use ZPHP\Socket\ICallback;
use ZPHP\Socket\IClient;
use ZPHP\Protocol;
use ZPHP\Core;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Queue\Factory as ZQueue;

class React implements ICallback
{
    private $_data;
    private $_conns;


    public function onStart()
    {
        echo 'server start' . PHP_EOL;
    }

    public function onConnect()
    {
        $params = func_get_args();
        $fd = (int)$params[0]->stream;
        $this->_conns[$fd] = $params[0];
        echo "Client {$fd}：Connect" . PHP_EOL;
    }

    public function onReceive()
    {
        $params = func_get_args();
        $data = trim($params[1]);
        echo $data . PHP_EOL;
        if (empty($data)) {
            return;
        }
        $server = Protocol\Factory::getInstance(Core\Config::getField('socket', 'protocol'));
        $workMode = ZConfig::getField('socket', 'work_mode', 1);
        if (1 === $workMode) { //多进程模式
            $result = $server->parse($data);
            if (empty($result['a'])) {
                if (!empty($result['fd'])) {
                    $fd = $result['fd'];
                    $this->_conns[$fd]->write($data . "\n");
                } else {
                    $params[0]->write($data . "\n");
                }
            } else {
                $fd = (int)$params[0]->stream;
                $server->setFd($fd);
                $server->display($result);
                $queueService = ZQueue::getInstance(ZConfig::getField('queue', 'adapter'));
                $queueService->add(ZConfig::getField('queue', 'key'), $server->getData());
            }
        } elseif (0 === $workMode) { //单进程模式
            $server->parse($data);
            $fd = (int)$params[0]->stream;
            $server->setFd($fd);
            $server = $this->route($server);
            $params[0]->write($server->getData() . "\n");
        } else { //多线程模式
            //TODO
        }
    }

    public function onClose()
    {
        $params = func_get_args();
        $conn = $params[0];
        $conn->end();
        $fd = (int)$params[0]->stream;
        unset($this->_conns[$fd]);
        echo "Client {$fd}：Close" . PHP_EOL;
    }

    public function onShutdown()
    {
        echo 'server close' . PHP_EOL;
    }

    public function display($data)
    {
        $this->_data = $data;
    }

    public function work()
    {
        $server = Protocol\Factory::getInstance(Core\Config::getField('socket', 'protocol'));
        $queueService = ZQueue::getInstance(ZConfig::getField('queue', 'adapter'));
        while (true) {
            $data = $queueService->get(ZConfig::getField('queue', 'key'));
            if (!empty($data)) {
                $result = $server->parse($data);
                if (!empty($result['fd'])) {
                    $server->setFd($result['fd']);
                }
                if (!empty($result)) {
                    $server = $this->route($server);
                    $server->sendMaster();
                }
            }
            usleep(500);
        }
    }

    private function route($server)
    {
        try {
            Core\Route::route($server);
        } catch (\Exception $e) {
            $server->display($e->getMessage());
        }
        return $server;
    }
}