<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core\Config;
use ZPHP\Common\MessagePacker;
use ZPHP\Protocol\IProtocol;

class ZRpack implements IProtocol
{
    private $_ctrl = 'main\\main';
    private $_method = 'main';
    private $_params = array();
    private $_buffer = array();
    private $fd;
    private $_data;
    private $_cmd;

    /**
     * client包格式： writeString(json_encode(array("pathinfo", array("参数列表") )));
     * server包格式：包总长+数据(json_encode)
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
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
        $packData->resetOffset(4);
        $this->_cmd = $packData->readInt();
        $pathinfo = Config::getField('cmdlist', $this->_cmd);
        $params = $packData->readString();
        $unpackData = \json_decode(gzdecode($params), true);
        $this->_params = $unpackData;
        $routeMap = ZRoute::match(Config::get('route', false), $pathinfo);
        if(is_array($routeMap)) {
            $this->_ctrl = $routeMap[0];
            $this->_method = $routeMap[1];
            if(!empty($routeMap[2]) && is_array($routeMap[2])) {
                //参数优先
                $this->_params = $this->_params + $routeMap[2];
            }
        }
        return $this->_params;
    }

    public function setFd($fd)
    {
        $this->fd = $fd;
    }

    public function getFdBuffer($fd)
    {
        return !empty($this->_buffer[$fd]) ? $this->_buffer[$fd] : false
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

    public function getCmd()
    {
        return $this->_cmd;
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
        $data['cmd'] = $this->_cmd;
        $this->_data = $data;
        echo $this->getData();
    }

    public function getData()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/zrpack; charset=utf-8");
        }
        $data = gzencode(\json_encode($this->_data));
        $pack = new MessagePacker();
        $len = strlen($data);
        $pack->writeInt($len+12);
        $pack->writeInt($this->_cmd);
        $pack->writeString($data, $len);
        $data = $pack->getData();
        $this->_data = null;
        $this->_cmd = null;
        return $pack->getData();
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