<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core\Config,
    ZPHP\Socket\Client as ZSClient,
    ZPHP\Protocol\IProtocol;

class Json implements IProtocol
{
    private $_action = 'index';
    private $_method = 'main';
    private $_params = array();
    private $_data;
    private $fd;

    public function parse($_data)
    {
        $data = \json_decode($_data, true);
        $apn = Config::getFiled('project', 'action_name', 'a');
        $mpn = Config::getFiled('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $this->_action = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $this->_method = $data[$mpn];
        }

        $fdpn = Config::getFiled('project', 'fd_name', 'fd');
        if (isset($data[$fdpn])) {
            $this->fd = $data[$fdpn];
        }
        $this->_params = $data;
        return $data;
    }

    public function setFd($fd)
    {
        $this->fd = $fd;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function display($model)
    {
        $data = array();
        if (is_array($model)) {
            $data = $model;
        } else {
            $data['data'] = $model;
        }
        $data['fd'] = $this->fd;
        $this->_data = $data;
    }

    public function getData()
    {
        $data = \json_encode($this->_data);
        $this->_data = null;
        return $data;
    }

    public function sendMaster()
    {
        $host = Config::getFiled('socket', 'host');
        $port = Config::getFiled('socket', 'port');
        $client = new ZSClient($host, $port);
        $client->send($this->getData());
    }
}
