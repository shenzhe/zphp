<?php
/**
 * Created by PhpStorm.
 * User: lancelot
 * Date: 15-12-6
 * Time: 上午12:25
 */

namespace ZPHP\Server\Adapter;
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Factory as ZProtocol;
use ZPHP\Socket\Factory as SFactory;
use ZPHP\Core\Config;
use ZPHP\Core\Factory as CFactory;
use ZPHP\Server\IServer;

class Hprose implements IServer{

    public function run()
    {
        $config = Config::get('socket');
        if (empty($config)) {
            throw new \Exception("socket config empty");
        }
        $socket = SFactory::getInstance($config['adapter'], $config);
        if(method_exists($socket, 'setClient')) {
            $client = CFactory::getInstance($config['client_class']);
            $socket->setClient($client);
        }
        Request::setServer(ZProtocol::getInstance(Config::getField('socket', 'protocol')));
        Request::setHttpServer(1);
        $socket->run();
    }
}

