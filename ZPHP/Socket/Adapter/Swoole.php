<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需扩展地址：https://github.com/matyhtf/swoole
 */

namespace ZPHP\Socket\Adapter;

use ZPHP\Core\Config;
use ZPHP\Socket\IServer;
use ZPHP\Socket\Callback;

class Swoole implements IServer
{
    private $client;
    private $config;
    private $serv;
    const TYPE_TCP = 'tcp';
    const TYPE_UDP = 'udp';
    const TYPE_HTTP = 'http';
    const TYPE_HTTPS = 'https';
    const TYPE_WEBSOCKET = 'ws';
    const TYPE_WEBSOCKETS = 'wss';

    public function __construct(array $config)
    {
        if (!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/swoole/swoole-src");
        }
        $this->config = $config;
        $socketType = empty($config['server_type']) ? self::TYPE_TCP : strtolower($config['server_type']);
        $this->config['server_type'] = $socketType;
        $workMode = empty($config['work_mode']) ? SWOOLE_PROCESS : $config['work_mode'];
        $ssl = 0;
        if (!empty($this->config['ssl_cert_file']) && !empty($this->config['ssl_key_file'])) {
            $ssl = SWOOLE_SSL;
        }
        switch ($socketType) {
            case self::TYPE_TCP:
                $this->serv = new \swoole_server($config['host'], $config['port'], $workMode, SWOOLE_SOCK_TCP | $ssl);
                break;
            case self::TYPE_UDP:
                $this->serv = new \swoole_server($config['host'], $config['port'], $workMode, SWOOLE_SOCK_UDP | $ssl);
                break;
            case self::TYPE_HTTP:
                $this->serv = new \swoole_http_server($config['host'], $config['port'], $workMode);
                break;
            case self::TYPE_HTTPS:
                if (!$ssl) {
                    throw new \Exception("https must set ssl_cert_file && ssl_key_file");
                }
                $this->serv = new \swoole_http_server($config['host'], $config['port'], $workMode, \SWOOLE_SOCK_TCP | \SWOOLE_SSL);
                break;
            case self::TYPE_WEBSOCKET:
                $this->serv = new \swoole_websocket_server($config['host'], $config['port'], $workMode);
                break;
            case self::TYPE_WEBSOCKETS:
                if (!$ssl) {
                    throw new \Exception("https must set ssl_cert_file && ssl_key_file");
                }
                $this->serv = new \swoole_websocket_server($config['host'], $config['port'], $workMode, \SWOOLE_SOCK_TCP | \SWOOLE_SSL);
                break;
        }

        if (!empty($config['addlisten']) && $socketType != self::TYPE_UDP && SWOOLE_PROCESS == $workMode) {
            $this->serv->addlistener($config['addlisten']['ip'], $config['addlisten']['port'], SWOOLE_SOCK_UDP);
        }

        if ('Ant' == Config::get('server_mode')) {
            if (!isset($this->config['open_length_check'])) {
                $this->config += [
                    'open_length_check' => true,
                    'package_length_type' => 'N',
                    'package_length_offset' => 0,       //第N个字节是包长度的值
                    'package_body_offset' => 4,       //第几个字节开始计算长度
                    'package_max_length' => 2000000,  //协议最大长度
                ];
                Config::set('socket', $this->config);
                Config::set('project', Config::get('project', []) + [
                        'default_ctrl_name' => 'main',
                        'protocol' => 'Ant',
                        'view_mode' => 'Ant',
                        'exception_handler' => 'exceptionHandler\BaseException::exceptionHandler',
                        'fatal_handler' => 'exceptionHandler\BaseException::fatalHandler',
                        'error_handler' => 'exceptionHandler\BaseException::errorHandler',
                    ]
                );
            }
        }

        $this->serv->set($this->config);
    }

    public function setClient($client)
    {
        if (!is_object($client)) {
            throw new \Exception('client must object');
        }
        switch ($this->config['server_type']) {
            case self::TYPE_WEBSOCKET:
            case self::TYPE_WEBSOCKETS:
                if (!($client instanceof Callback\SwooleWebSocket)) {
                    throw new \Exception('client must instanceof ZPHP\Socket\Callback\SwooleWebSocket');
                }
                break;
            case self::TYPE_HTTP:
            case self::TYPE_HTTPS:
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
        switch ($this->config['server_type']) {
            case self::TYPE_TCP:
                $this->serv->on('Receive', array($this->client, 'doReceive'));
                break;
            case self::TYPE_HTTP:
            case self::TYPE_HTTPS:
                $this->serv->on('Request', array($this->client, 'doRequest'));
                break;
            case self::TYPE_WEBSOCKET:
            case self::TYPE_WEBSOCKETS:
                if (method_exists($this->client, 'onHandShake')) {
                    $this->serv->on('HandShake', array($this->client, 'onHandShake'));
                }
                if (method_exists($this->client, 'onOpen')) {
                    $this->serv->on('Open', array($this->client, 'onOpen'));
                }
                if (method_exists($this->client, 'doRequest')) {
                    $this->serv->on('Request', array($this->client, 'doRequest'));
                }
                $this->serv->on('Message', array($this->client, 'onMessage'));
                break;
            case self::TYPE_UDP:
                array_pop($handlerArray);
                $this->serv->on('Packet', array($this->client, 'doPacket'));
                break;
        }

        foreach ($handlerArray as $handler) {
            if (method_exists($this->client, $handler)) {
                $this->serv->on(\substr($handler, 2), array($this->client, $handler));
            }
        }

        if (!empty($this->config['start_hook']) && is_callable($this->config['start_hook'])) {
            if ($this->config['start_hook_args']) {
                call_user_func($this->config['start_hook'], $this->serv);
            } else {
                call_user_func($this->config['start_hook']);
            }
        }
        $this->serv->start();
    }
}
