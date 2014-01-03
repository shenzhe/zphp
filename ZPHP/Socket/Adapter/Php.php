<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 所需扩展地址：https://github.com/bzick/php-libevent
 */


namespace ZPHP\Socket\Adapter;
use ZPHP\Socket\IServer;

class Php implements IServer
{
    public $client;
    private $config;
    public $timeout;
    public $buffer_size = 8192;
    public $write_buffer_size = 2097152;
    public $base_event;
    public $server_event;
    public $server_sock;
    public $max_connect = 10000;
    public $client_sock = array();
    public $client_num = 0;

    public function __construct($config)
    {
        if (!\extension_loaded('pcntl')) {
            throw new \Exception("Require pcntl extension!");
        }
        $this->config = $config;
    }

    private function init()
    {
        $this->base_event = \event_base_new();
        $this->server_event = \event_new();
    }

    public function setClient($client)
    {
        $this->client = $client;
        $this->client->server = $this;
    }

    private function create($uri, $block = 0)
    {
        $socket = \stream_socket_server($uri, $errno, $errstr);
        if (!$socket) {
            throw new \Exception($errno . $errstr);
        }
        \stream_set_blocking($socket, $block);
        return $socket;
    }

    public function accept()
    {
        $client_socket = \stream_socket_accept($this->server_sock);
        $client_socket_id = (int)$client_socket;
        \stream_set_blocking($client_socket, 0);
        $this->client_sock[$client_socket_id] = $client_socket;
        $this->client_num++;
        if ($this->client_num > $this->max_connect) {
            $this->_closeSocket($client_socket);
            return false;
        } else {
            //设置写缓冲区
            \stream_set_write_buffer($client_socket, $this->write_buffer_size);
            return $client_socket_id;
        }
    }


    public function run()
    {
        $this->init();
        //建立服务器端Socket
        $this->server_sock = $this->create("tcp://{$this->config['host']}:{$this->config['port']}");
        //设置事件监听，监听到服务器端socket可读，则有连接请求
        \event_set($this->server_event, $this->server_sock, EV_READ | EV_PERSIST, __CLASS__ . '::server_handle_connect', $this);
        \event_base_set($this->server_event, $this->base_event);
        \event_add($this->server_event);
        $this->client->onStart();
        \event_base_loop($this->base_event);
    }

    /**
     * 向client发送数据
     * @param $client_id
     * @param $data
     * @return unknown_type
     */
    public function _send($client_id, $data)
    {
        $length = \strlen($data);
        for ($written = 0; $written < $length; $written += $fwrite) {
            $fwrite = \stream_socket_sendto($client_id, substr($data, $written));
            if ($fwrite <= 0 or $fwrite === false) {
                return $written;
            }
        }
        return $written;
    }

    public function send($cilent_id, $data)
    {
        if (isset($this->client_sock[$cilent_id])) {
            return $this->_send($this->client_sock[$cilent_id], $data);
        }
    }

    public function sendAll($client_id, $data)
    {
        foreach ($this->client_sock as $k => $sock) {
            if ($client_id and $k == $client_id) {
                continue;
            }
            $this->_send($sock, $data);
        }

        return TRUE;
    }

    /**
     * 关闭服务器程序
     * @return unknown_type
     */
    public function shutdown()
    {
        //关闭所有客户端
        foreach ($this->client_sock as $k => $sock) {
            $this->_closeSocket($sock, $this->client_event[$k]);
        }
        //关闭服务器端
        $this->_closeSocket($this->server_sock, $this->server_event);
        //关闭事件循环
        \event_base_loopexit($this->base_event);
        $this->client->onShutdown();
    }

    private function _closeSocket($socket, $event = null)
    {
        if ($event) {
            \event_del($event);
            \event_free($event);
        }
        \stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        \fclose($socket);
    }

    public function close($client_id)
    {
        $this->_closeSocket($this->client_sock[$client_id], $this->client_event[$client_id]);
        unset($this->client_sock[$client_id], $this->client_event[$client_id]);
        $this->client->onClose($client_id);
        $this->client_num--;
    }

    public static function server_handle_connect($server_socket, $events, $server)
    {
        if ($client_id = $server->accept()) {
            $client_socket = $server->client_sock[$client_id];
            //新的事件监听，监听客户端发生的事件
            $client_event = \event_new();
            \event_set($client_event, $client_socket, EV_READ | EV_PERSIST, __CLASS__ . "::server_handle_receive", array($server, $client_id));
            //设置基本时间系统
            \event_base_set($client_event, $server->base_event);
            //加入事件监听组
            \event_add($client_event);
            $server->client_event[$client_id] = $client_event;
            $server->client->onConnect($client_id);
        }
    }

    /**
     * 接收到数据后进行处理
     * @param $client_socket
     * @param $events
     * @param $arg
     * @return unknown_type
     */
    public static function server_handle_receive($client_socket, $events, $arg)
    {
        $server = $arg[0];
        $client_id = $arg[1];
        $data = self::fread_stream($client_socket, $server->buffer_size);

        if ($data !== false) {
            $server->client->onReceive($client_id, $data);
        } else {
            $server->close($client_id);
        }
    }

    private static function fread_stream($fp, $length)
    {

        $data = false;
        while ($buf = \stream_socket_recvfrom($fp, $length)) {
            $data .= $buf;
            if (\strlen($buf) < $length)
                break;
        }
        return $data;
    }

}
