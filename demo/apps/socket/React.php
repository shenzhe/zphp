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
    private $_server;
    private $_reqnum=0;


    public function onStart()
    {
        echo 'server start' . PHP_EOL;
        $params = func_get_args();
        $this->_server = $params[0];
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
        if (3 === $workMode) { //多进程模式
            $this->_server->check();
            $result = $server->parse($data);
            if (empty($result['a'])) {
                if (!empty($result['fd'])) {
                    $fd = $result['fd'];
                    $this->_conns[$fd]->write($data . "\n");
                    $this->_server->addRequest($result['pid']);
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
        } elseif (1 === $workMode) { //单进程模式
            $result = $server->parse($data);
            if (empty($result['a'])) {
                $params[0]->write("server:{$data}" . "\n");
            } else {
                $fd = (int)$params[0]->stream;
                $server->setFd($fd);
                $server = $this->route($server);
                $params[0]->write($server->getData() . "\n");
            }
        } else { //多线程模式
            new ReactThread($params[0], $data, $server);
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
                $this->_reqnum++;
                $result = $server->parse($data);
                if (!empty($result['fd'])) {
                    $server->setFd($result['fd']);
                }
                if (!empty($result)) {
                    $server = $this->route($server);
                    $server->sendMaster([
                        'pid' => posix_getpid(),
                        'reqnum'=>$this->_reqnum,
                    ]);
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