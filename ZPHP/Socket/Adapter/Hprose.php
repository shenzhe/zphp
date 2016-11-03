<?php

namespace ZPHP\Socket\Adapter;

use Hprose\Swoole\Server;
use ZPHP\Socket\IServer;

class Hprose implements IServer
{
    private $client;
    private $config;

    /**
     * @var \Hprose\Swoole\Server
     */
    private $serv;

    public function __construct(array $config)
    {
        if (!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $this->config = $config;
        if (!isset($config['server_type']))
            $config['server_type'] = 'http';
        $this->serv = new Server("{$config['server_type']}://{$config['host']}:{$config['port']}");

        $this->serv->setErrorTypes(E_ALL);
        $this->serv->setDebugEnabled();

        $this->serv->set($config);
    }

    public function setClient($client)
    {
        if (!is_object($client)) {
            throw new \Exception('client must object');
        }
        $this->client = $client;
        $this->client->setServ($this->serv);
        return true;
    }

    public function run()
    {
        $handlerArray = array(
            'onWorkerStart',
            'onWorkerStop',
            'onWorkerError',
            'onTask',
            'onFinish',
            'onWorkerError',
            'onManagerStart',
            'onManagerStop',
            'onPipeMessage',
        );
        $this->serv->on('Start', array($this->client, 'onStart'));
        $this->serv->on('Shutdown', array($this->client, 'onShutdown'));

        foreach ($handlerArray as $handler) {
            if (method_exists($this->client, $handler)) {
                $this->serv->on(\substr($handler, 2), array($this->client, $handler));
            }
        }
        $this->client->onRegister();
        $this->serv->start();
    }
}
