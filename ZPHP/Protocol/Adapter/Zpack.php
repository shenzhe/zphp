<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core\Config;
use ZPHP\Common\MessagePacker;
use ZPHP\Protocol\IProtocol;

class Zpack implements IProtocol
{
    private $_ctrl = 'main\\main';
    private $_method = 'main';
    private $_params = array();
    private $_buffer = array();
    private $fd;
    private $_data;

    /**
     * client包格式： writeString(json_encode(array("a"='main/main',"m"=>'main', 'k1'=>'v1')));
     * server包格式：包总长+数据(json_encode)
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
        $this->_ctrl = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $this->_method = Config::getField('project', 'default_method_name', 'main');
        if (!empty($this->_buffer[$this->fd])) {
            $_data = $this->_buffer . $_data;
        }
        $packData = new MessagePacker($_data);
        $packLen = $packData->readInt();
        $dataLen = \strlen($_data);
        if ($packLen > $dataLen) {
            $this->_buffer[$this->fd] = $_data;
            return false;
        } elseif ($packLen < $dataLen) {
            $this->_buffer[$this->fd] = \substr($_data, $packLen, $dataLen - $packLen);
        } else {
            if (!empty($this->_buffer[$this->fd])) {
                unset($this->_buffer[$this->fd]);
            }
        }
        $packData->resetOffset();
        $params = $packData->readString();
        $this->_params = \json_decode($params, true);
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($params[$apn])) {
            $this->_ctrl = \str_replace('/', '\\', $params[$apn]);
        }
        if (isset($params[$mpn])) {
            $this->_method = $params[$mpn];
        }
        return $this->_params;
    }

    public function setFd($fd)
    {
        $this->fd = $fd;
    }

    public function getFdBuffer($fd)
    {
        return !empty($this->_buffer[$fd]) ? $this->_buffer[$fd] : false;
    }

    public function getCtrl()
    {
        return $this->_ctrl;
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
        return $this->_data;
    }

    public function getData()
    {
        $data = \json_encode($this->_data);
        $pack = new MessagePacker();
        $pack->writeString($data);
        $data = $pack->getData();
        $this->_data = null;
        return $data;
    }

    public function sendMaster(array $_params=null)
    {
        if(!empty($_params)) {
            $this->_data = $this->_data + $_params;
        }
        $host = Config::getField('socket', 'host');
        $port = Config::getField('socket', 'port');
        $client = new ZSClient($host, $port);
        $client->send($this->getData());
    }
}