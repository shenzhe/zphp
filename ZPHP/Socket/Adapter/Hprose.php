<?php

namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer,
    ZPHP\Socket\Callback;

class Hprose implements IServer
{
    private $client;
    private $config;
    private $serv;

    public function __construct(array $config)
    {
        if(!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $this->config = $config;
        $this->serv = new \HproseSwooleServer("http://{$config['host']}:{$config['port']}");

        $this->serv->setErrorTypes(E_ALL);
        $this->serv->setDebugEnabled();

        $this->serv->set($config);
    }

    public function setClient($client)
    {
        if(!is_object($client)) {
            throw new \Exception('client must object');
        }
        $this->client = $client;
        $this->client->setServ($this->serv);
        return true;
    }

    public function run()
    {
        $this->client->onRegister();
        $this->serv->start();
    }
}
