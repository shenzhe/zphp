<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\Core\Config;
use ZPHP\View;
use ZPHP\Protocol\IProtocol;

class Cli implements IProtocol
{
    private $_ctrl = 'index';
    private $_method = 'main';
    private $_params = array();
    private $_view_mode;

    /**
     * 会取$_SERVER['argv']最后一个参数
     * 原始格式： a=action&m=method&param1=val1
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
        $this->_ctrl = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $this->_method = Config::getField('project', 'default_method_name', 'main');
        \parse_str(array_pop($_data), $data);
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

    public function setViewMode($mode)
    {
        $this->_view_mode = $mode;
    }

    public function display($model)
    {
        if (empty($this->_view_mode)) {
            $viewMode = Config::getField('project', 'view_mode', 'String');
        } else {
            $viewMode = $this->_view_mode;
        }
        $this->_view_mode = '';
        $view = View\Factory::getInstance($viewMode);
        $view->setModel($model);
        $view->display();
    }
}
