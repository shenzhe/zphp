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

class React implements ICallback
{
    private $_data;
    private $_msgQueue;
    private $_conns;

    public function setQueue($queue)
    {
        $this->_msgQueue = $queue;
    }

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
        $server = Protocol\Factory::getInstance(Core\Config::getFiled('socket', 'protocol'));
        $result = $server->parse($data);
        if (empty($result['a'])) {
            if(!empty($result['fd'])) {
                $fd = $result['fd'];
                $this->_conns[$fd]->write($data);
            } else {
                $params[0]->write($data);
            }
        } else {
            $fd = (int)$params[0]->stream;
            $result['fd'] = $fd;
            $server->display($result);
            msg_send($this->_msgQueue, 1, $server->getData());
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
        $server = Protocol\Factory::getInstance(Core\Config::getFiled('socket', 'protocol'));
        while (true) {
            msg_receive($this->_msgQueue, 0, $messageType, 1024, $data, true, MSG_IPC_NOWAIT);
            if (!empty($data)) {
                $result = $server->parse($data);
                if (!empty($result)) {
                    try {
                        Core\Route::route($server);
                    } catch (\Exception $e) {
                        $server->display($e->getMessage());
                    }
                    $server->sendMaster();
                }
            }
            usleep(500);
        }
    }
}