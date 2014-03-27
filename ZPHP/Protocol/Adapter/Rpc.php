<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\Core\Config;
use ZPHP\Protocol\IProtocol;

class Rpc implements IProtocol
{
    private $_ctrl = 'main\\main';
    private $_method = 'main';
    private $_params = array();

    /**
     * 直接 parse $_REQUEST
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
        $this->_ctrl = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $this->_method = Config::getField('project', 'default_method_name', 'main');
        $data = $_data;
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $this->_ctrl = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $this->_method = $data[$mpn];
        }
        $this->_params = $data;
        return true;
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
        return $model;
    }
}
