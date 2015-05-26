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
    private $_ctrl = 'main\\main';
    private $_method = 'main';
    private $_params = array();
    private $_data;
    private $fd;

    public function parse($_data)
    {
        $this->_ctrl = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $this->_method = Config::getField('project', 'default_method_name', 'main');
        $data = \json_decode($_data, true);
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $this->_ctrl = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $this->_method = $data[$mpn];
        }

        $fdpn = Config::getField('project', 'fd_name', 'fd');
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

    public function getFd(){
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

    public function display($model)
    {
        if (empty($this->_view_mode)) {
            $viewMode = Config::getField('project', 'view_mode', 'Json');
        } else {
            $viewMode = $this->_view_mode;
        }
        $this->_view_mode = '';
        $view = View\Factory::getInstance($viewMode);
        $view->setModel($model);
        return $view->display();
    }

    public function getData()
    {
        $data = \json_encode($this->_data);
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
