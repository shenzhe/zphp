<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需扩展地址：https://github.com/matyhtf/swoole
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer,
    ZPHP\Socket\ICallback;

class Swoole implements IServer
{
    private $client;
    private $config;
    private $serv;

    public function __construct(array $config)
    {
        if(!\extension_loaded('swoole')) {
            throw new \Exception("no swoole extension. get: https://github.com/matyhtf/swoole");
        }
        $this->config = $config;
        $this->serv = new \swoole_server($config['host'], $config['port'], $config['work_mode']);
        $this->serv->set(array(
            'timeout' => empty($config['timeout']) ? 2 : $config['timeout'], //select and epoll_wait timeout.
            'poll_thread_num' => empty($config['poll_thread_num']) ? 2 : $config['poll_thread_num'], //reactor thread num
            'writer_num' => empty($config['writer_num']) ? 2 : $config['writer_num'], //writer thread num
            'worker_num' => empty($config['worker_num']) ? 2 : $config['worker_num'], //worker process num
            'task_worker_num' => empty($config['task_worker_num']) ? 0 : $config['task_worker_num'], //task worker process num
            'backlog' => empty($config['backlog']) ? 128 : $config['backlog'], //listen backlog));
            'max_request' => empty($config['max_request']) ? 1000 : $config['max_request'],
            'max_conn' => empty($config['max_conn']) ? 100000 : $config['max_conn'],
            'dispatch_mode' => empty($config['dispatch_mode']) ? 2 : $config['dispatch_mode'],
            'heartbeat_idle_time' => empty($config['heartbeat_idle_time']) ? 0 : $config['heartbeat_idle_time'],
            'heartbeat_check_interval' => empty($config['heartbeat_check_interval']) ? 0 : $config['heartbeat_check_interval'],
            'log_file' => empty($config['log_file']) ? '/tmp/swoole.log' : $config['log_file'],
            'daemonize' => empty($config['daemonize']) ? 0 : 1,
        ));
    }

    public function setClient($client)
    {
        $this->client = $client;
        return true;
    }

    public function run()
    {
        $this->serv->on('Start', array($this->client, 'onStart'));
        $this->serv->on('Connect', array($this->client, 'onConnect'));
        $this->serv->on('Receive', array($this->client, 'onReceive'));
        $this->serv->on('Close', array($this->client, 'onClose'));
        $this->serv->on('Shutdown', array($this->client, 'onShutdown'));
        $handlerArray = array(
            'onTimer', 
            'onWorkerStart', 
            'onWorkerStop', 
            'onMasterConnect', 
            'onMasterClose', 
            'onTask', 
            'onFinish',
            'onWorkerError',
        );
        foreach($handlerArray as $handler) {
            if(method_exists($this->client, $handler)) {
                $this->serv->on(\str_replace('on', '', $handler), array($this->client, $handler));
            }
        } 
        $this->serv->start();
    }
}
