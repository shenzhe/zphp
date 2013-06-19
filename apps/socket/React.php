<?php
/**
 * User: shenzhe
 * Date: 13-6-19
 */
namespace socket;
use ZPHP\Socket\IClient;
use ZPHP\Protocol;
use ZPHP\Core;

class React implements IClient
{
    private $_data;
    public function onStart()
    {
        echo 'server start' . PHP_EOL;
    }

    public function onConnect()
    {
        $params = func_get_args();
        $fd = (int)$params[0]->stream;
        echo "Client {$fd}：Connect" . PHP_EOL;
    }

    public function onReceive()
    {
        $params = func_get_args();
        $conn = $params[0];
        $data = trim($params[1]);
        echo $data . PHP_EOL;
        if (empty($data)) {
            return;
        }
        $socketConfig = Core\Config::get('socket');
        $server = Protocol\Factory::getInstance($socketConfig['protocol'], $data);
        Core\Route::route($server);
        $conn->write($server->getData());
    }

    public function onClose()
    {
        $params = func_get_args();
        $conn = $params[0];
        $conn->end();
        $fd = (int)$params[0]->stream;
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
}