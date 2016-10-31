<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;

use ZPHP\Protocol\Request;
use ZPHP\Protocol\Factory as ZProtocol;
use ZPHP\Socket\Factory as SFactory;
use ZPHP\Core\Config;
use ZPHP\Core\Factory as CFactory;
use ZPHP\Server\IServer;

class Socket implements IServer
{
    public function run()
    {
        $config = Config::get('socket');
        if (empty($config)) {
            throw new \Exception("socket config empty");
        }
        $socket = SFactory::getInstance($config['adapter'], $config);
        if (method_exists($socket, 'setClient')) {
            $client = CFactory::getInstance($config['client_class']);
            $socket->setClient($client);
        }
        Request::setServer(ZProtocol::getInstance(Config::getField('socket', 'protocol')));
        Request::setLongServer();
        Request::setHttpServer(0);
        $socket->run();
    }
}