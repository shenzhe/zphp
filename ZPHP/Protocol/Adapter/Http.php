<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\View;
use ZPHP\Core\Config;
use ZPHP\Protocol\IProtocol;
use ZPHP\Common\Route as ZRoute;
use ZPHP\Common\Utils as ZUtils;

class Http implements IProtocol
{
    private $_ctrl = 'main\\main';
    private $_method = 'main';
    private $_params = array();
    private $_view_mode = '';
    private $_tpl_file = '';

    /**
     * 直接 parse $_REQUEST
     * @param $_data
     * @return bool
     */
    public function parse($data)
    {
        $this->_ctrl = Config::getField('project', 'default_ctrl_name', 'main\\main');
        $this->_method = Config::getField('project', 'default_method_name', 'main');
        $apn = Config::getField('project', 'ctrl_name', 'a');
        $mpn = Config::getField('project', 'method_name', 'm');
        if (isset($data[$apn])) {
            $this->_ctrl = \str_replace('/', '\\', $data[$apn]);
        }
        if (isset($data[$mpn])) {
            $this->_method = $data[$mpn];
        }
        if(!empty($_SERVER['PATH_INFO'])) {
            $routeMap = ZRoute::match(Config::get('route', false), $_SERVER['PATH_INFO']);
            if(is_array($routeMap)) {
                $this->_ctrl = $routeMap[0];
                $this->_method = $routeMap[1];
                if(!empty($routeMap[2]) && is_array($routeMap[2])) {
                    //参数优先
                    $data = $data + $routeMap[2];
                }
            }
        }
        $this->_params = $data;
        $this->_tpl_file = str_replace('\\', DS, $this->_ctrl) . DS . $this->_method . '.php';
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

    public function setFd($fd)
    {
        $this->_fd = $fd;
    }

    public function getFd()
    {
        return $this->_fd;
    }

    public function setViewMode($mode)
    {
        $this->_view_mode = $mode;
    }

    public function setTplFile($tpl)
    {
        $this->_tpl_file = $tpl;
    }

    public function display($model)
    {
        if(is_array($model)) {
            if(!empty($model['_view_mode'])) {
                $viewMode = $model['_view_mode'];
                unset($model['_view_mode']);
            } else {
                if (empty($this->_view_mode)) {
                    $viewMode = Config::getField('project', 'view_mode', '');
                } else {
                    $viewMode = $this->_view_mode;
                    $this->_view_mode = '';
                }
            }
        }

        if(empty($viewMode)) {
            if (ZUtils::isAjax()) {
                $viewMode = 'Json';
            } else {
                $viewMode = 'Php';
            }
        }

        $view = View\Factory::getInstance($viewMode);
        if ('Php' === $viewMode) {
            if(is_array($model) && !empty($model['_tpl_file'])) {
                $view->setTpl($model['_tpl_file']);
                unset($model['_tpl_file']);
            } else if(!empty($this->_tpl_file)){
                $view->setTpl($this->_tpl_file);
                $this->_tpl_file = null;
            } else {
                throw new \Exception("tpl file empty");
            }
        }
        $view->setModel($model);
        $view->display();

    }
}
