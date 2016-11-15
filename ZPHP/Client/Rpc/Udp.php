<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/10/14
 * Time: 11:06
 */

namespace ZPHP\Client\Rpc;

use ZPHP\Core\Config;

class Udp
{
    private static $clients = [];
    private static $configs = [];
    /**
     * @var \swoole_client
     */
    private $client;
    private $api = '';
    private $sync = 1;

    private $config = [];

    /**
     * Tcp constructor.
     * @param $host
     * @param $port
     * @param int $timeOut
     * @param array $config
     * @throws \Exception
     */
    public function __construct($host, $port, $timeOut = 500, $config = array())
    {
        if (empty($timeOut) || $timeOut < 1) {
            $timeOut = 500;
        }
        $key = $host . ':' . $port . ':' . $timeOut;
        if (!isset(self::$clients[$key])) {
            $client = new \swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
            $ret = $client->connect($host, $port, $timeOut / 1000);
            if ($ret) {
                self::$clients[$key] = $client;
                self::$configs[$key] = $config;
            } else {
                throw new \Exception('connect server error', -1);
            }
            $this->api = Config::getField('project', 'default_ctrl_name');
        }
        $this->client = self::$clients[$key];
        $this->config = self::$configs[$key];
        return true;
    }

    public function setApi($api)
    {
        $this->api = $api;
        return $this;
    }

    public function noSync()
    {
        $this->sync = 0;
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function isConnected()
    {
        if (empty($this->client)) {
            return false;
        }

        return $this->client->isConnected();
    }

    public function call($method, $data = [])
    {
        $sendArr = [
            '_recv' => $this->sync,
            $this->config['method_name'] => $method,
        ];
        if ($this->api) {
            $sendArr[$this->config['ctrl_name']] = $this->api;
        }
        $sendArr += $data;
        $result = json_encode($sendArr);
        return $this->client->send($result);
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments[0])) {
            $arguments[0] = [];
        } elseif (!is_array($arguments[0])) {
            throw new \Exception('arguments[0] must array');
        }
        return $this->call($name, $arguments[0]);
    }
}