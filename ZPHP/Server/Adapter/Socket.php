<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Socket\Factory as SFactory;
use ZPHP\Core\Config;
use ZPHP\Core\Factory as CFactory;

class Socket
{
    public function run()
    {
        $config = Config::get('socket');
        if (empty($config)) {
            throw new \Exception("socket config empty");
        }
        $socket = SFactory::getInstance($config['adpter'], $config);
        $client = CFactory::getInstance($config['client_class']);
        $socket->setClient($client);
        $socket->run();
    }
}