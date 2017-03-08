<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;

use ZPHP\Protocol\Request;
use ZPHP\Protocol\Factory as ZProtocol;
use ZPHP\Socket\Adapter\Swoole;
use ZPHP\Socket\Factory as SFactory;
use ZPHP\Core\Config;
use ZPHP\Core\Factory as CFactory;
use ZPHP\Server\IServer;

class Ant implements IServer
{
    public function run()
    {
        $config = Config::get('socket');
        if (empty($config)) {
            throw new \Exception("socket config empty");
        }
        $socket = SFactory::getInstance(Config::getField('socket', 'adapter', 'Swoole'), $config);
        if (method_exists($socket, 'setClient')) {
            $type = Config::getField('socket', 'server_type');
            $clientClass = 'socket\Tcp';
            switch ($type) {
                case Swoole::TYPE_WEBSOCKET:
                case Swoole::TYPE_WEBSOCKETS:
                    $clientClass = 'socket\WebSocket';
                    break;
                case Swoole::TYPE_HTTP:
                case Swoole::TYPE_HTTPS:
                    $clientClass = 'socket\Http';
                    break;
                case Swoole::TYPE_UDP:
                    $clientClass = 'socket\Udp';
                    break;
            }
            $client = CFactory::getInstance(Config::getField('socket', 'client_class', $clientClass));
            $socket->setClient($client);
        }
        Request::setServer(ZProtocol::getInstance(Config::getField('socket', 'protocol', 'Ant')));
        Request::setLongServer();
        Request::setHttpServer(0);
        $socket->run();
    }
}