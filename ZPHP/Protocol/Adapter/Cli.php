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
    private $_action = 'index';
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
        \parse_str(array_pop($_data), $data);
        $apn = Config::getField('project', 'action_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $this->_action = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $this->_method = $data[$mpn];
        }
        $this->_params = $data;
        return true;
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
