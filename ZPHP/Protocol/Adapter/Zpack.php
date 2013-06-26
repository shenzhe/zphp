<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\Common\MessagePacker;
use ZPHP\Protocol\IProtocol;

class Zpack implements IProtocol
{
    private $_action = 'main\main';
    private $_method = 'main';
    private $_params = array();
    private $_buffer = array();
    private $fd;

    /**
     * client包格式： 包总长+action+method+params(json_encode)
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
        $this->_action = $packData->readString();
        $this->_method = $packData->readString();
        $params = $packData->readString();
        $this->_params = \json_decode($params, true);
        return true;
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
        $data = \json_encode($model);
        $dataLen = \strlen($data);
        $pack = new MessagePacker();
        $pack->writeInt($dataLen);
        $pack->writeString($data);
        return $pack->getData();
    }
}