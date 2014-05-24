<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core\Config;
use ZPHP\Common\MessagePacker;
use ZPHP\Protocol\IProtocol;
use ZPHP\Cache\Factory as ZCache;
use ZPHP\Common\Route as ZRoute;

class ZRpack implements IProtocol
{
    private $_ctrl = 'main\\main';
    private $_method = 'main';
    private $_params = array();
    private $fd;
    private $_data;
    private $_cmd;
    private $_cache;
    private $_rid = 0;

    /**
     * 包格式： 包总长+命令id+请求id+数据
     * 
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
        $this->_ctrl = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $this->_method = Config::getField('project', 'default_method_name', 'main');
        if (empty($this->_cache)) {
            $this->_cache = ZCache::getInstance('Php');
        }
        $cacheData = $this->_cache->get($this->fd);
        if (!empty($cacheData)) {
            $_data = $cacheData . $_data;
            $this->_cache->delete($this->fd);
        }
        if (empty($_data)) {
            return false;
        }
        $packData = new MessagePacker($_data);
        $packLen = $packData->readInt();
        $dataLen = \strlen($_data);
        if ($packLen > $dataLen) {
            $this->_cache->set($this->fd, $_data);
            return false;
        } elseif ($packLen < $dataLen) {
            $this->_cache->set($this->fd,  \substr($_data, $packLen, $dataLen - $packLen));
        }
        $packData->resetOffset(4);
        $this->_cmd = $packData->readInt();
        $pathinfo = Config::getField('cmdlist', $this->_cmd);
        $this->_rid = $packData->readInt();
        $params = $packData->readString();
        $unpackData = \json_decode(gzdecode($params), true);
        if(!empty($unpackData) && is_array($unpackData)) {
            $this->_params = $unpackData;
        }
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

    public function getFd($fd)
    {
        return $this->fd;
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
    public function getRid()
    {
        return $this->_rid;
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
        $data['rid'] = $this->_rid;
        $this->_data = $data;
        return array($data, $this->getData());
    }

    public function getData()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/zrpack; charset=utf-8");
        }
        $data = $this->_data;
        unset($data['cmd'], $data['fd']);
        $data = gzencode(\json_encode($data));
        $pack = new MessagePacker();
        $len = strlen($data);
        $pack->writeInt($len+16);
        $pack->writeInt($this->_cmd);
        $pack->writeInt($this->_rid);
        $pack->writeString($data, $len);
        $data = $pack->getData();
        $this->_data = null;
        $this->_cmd = null;
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