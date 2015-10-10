<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需扩展地址：https://github.com/matyhtf/swoole
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer,
    ZPHP\Socket\Callback;

class Swoole implements IServer
{
    private $client;
    private $config;
    private $serv;
    const TYPE_TCP = 'tcp';
    const TYPE_UDP = 'udp';
    const TYPE_HTTP = 'http';
    const TYPE_WEBSOCKET = 'ws';

    public function __construct(array $config)
    {
        if(!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $this->config = $config;
        $socketType = empty($config['server_type']) ? self::TYPE_TCP : strtolower($config['server_type']);
        $this->config['server_type'] = $socketType;
        switch($socketType) {
            case self::TYPE_TCP:
                $this->serv = new \swoole_server($config['host'], $config['port'], $config['work_mode'], SWOOLE_SOCK_TCP);
                break;
            case self::TYPE_UDP:
                $this->serv = new \swoole_server($config['host'], $config['port'], $config['work_mode'], SWOOLE_SOCK_UDP);
                break;
            case self::TYPE_HTTP:
                $this->serv = new \swoole_http_server($config['host'], $config['port'], $config['work_mode']);
                break;
            case self::TYPE_WEBSOCKET:
                $this->serv = new \swoole_websocket_server($config['host'], $config['port'], $config['work_mode']);
                break;

        }

        if(!empty($config['addlisten']) && $socketType != self::TYPE_UDP && SWOOLE_PROCESS == $config['work_mode']) {
            $this->serv->addlistener($config['addlisten']['ip'], $config['addlisten']['port'], SWOOLE_SOCK_UDP);
        }

        $this->serv->set($config);
    }

    public function setClient($client)
    {
        if(!is_object($client)) {
            throw new \Exception('client must object');
        }
        switch($this->config['server_type']) {
            case self::TYPE_WEBSOCKET:
                if (!($client instanceof Callback\SwooleWebSocket)) {
                    throw new \Exception('client must instanceof ZPHP\Socket\Callback\SwooleWebSocket');
                }
                break;
            case self::TYPE_HTTP:
                if (!($client instanceof Callback\SwooleHttp)) {
                    throw new \Exception('client must instanceof ZPHP\Socket\Callback\SwooleHttp');
                }
                break;
            case self::TYPE_UDP:
                if (!($client instanceof Callback\SwooleUdp)) {
                    throw new \Exception('client must instanceof ZPHP\Socket\Callback\SwooleUdp');
                }
                break;
            default:
                if (!($client instanceof Callback\Swoole)) {
                    throw new \Exception('client must instanceof ZPHP\Socket\Callback\Swoole');
                }
                break;
        }
        $this->client = $client;
        return true;
    }

    public function run()
    {
        $handlerArray = array(
            'onTimer',
            'onWorkerStart',
            'onWorkerStop',
            'onWorkerError',
            'onTask',
            'onFinish',
            'onWorkerError',
            'onManagerStart',
            'onManagerStop',
            'onPipeMessage',
            'onPacket',
        );
        $this->serv->on('Start', array($this->client, 'onStart'));
        $this->serv->on('Shutdown', array($this->client, 'onShutdown'));
        $this->serv->on('Connect', array($this->client, 'onConnect'));
        $this->serv->on('Close', array($this->client, 'onClose'));
        switch($this->config['server_type']) {
            case self::TYPE_TCP:
                $this->serv->on('Receive', array($this->client, 'onReceive'));
                break;
            case self::TYPE_HTTP:
                $this->serv->on('Request', array($this->client, 'onRequest'));
                break;
            case self::TYPE_WEBSOCKET:
                if(method_exists($this->client, 'onHandShake')) {
                    $this->serv->on('HandShake', array($this->client, 'onHandShake'));
                }
                if(method_exists($this->client, 'onOpen')) {
                    $this->serv->on('Open', array($this->client, 'onOpen'));
                }
                if(method_exists($this->client, 'onRequest')) {
                    $this->serv->on('Request', array($this->client, 'onRequest'));
                }
                $this->serv->on('Message', array($this->client, 'onMessage'));
                break;
            case self::TYPE_UDP:
                array_pop($handlerArray);
                $this->serv->on('Packet', array($this->client, 'onPacket'));
                break;
        }

        foreach($handlerArray as $handler) {
            if(method_exists($this->client, $handler)) {
                $this->serv->on(\substr($handler, 2), array($this->client, $handler));
            }
        } 
        $this->serv->start();
    }
}
