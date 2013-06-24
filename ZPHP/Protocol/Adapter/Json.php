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
        if (isset($data['a'])) {
            $this->_action = \str_replace('/', '\\', $data['a']);
            //unset($data['a']);
        }
        if (isset($data['m'])) {
            $this->_method = $data['m'];
            //unset($data['m']);
        }

        if (isset($data['fd'])) {
            $this->fd = $data['fd'];
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
        $this->_data = $model;
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
