<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\Protocol\IProtocol;

class Cli implements IProtocol
{
    private $_action = 'index';
    private $_method = 'main';
    private $_params = array();

    /**
     * 会取$_SERVER['argv']最后一个参数
     * 原始格式： a=action&m=method&param1=val1
     * @param $_data
     * @return bool
     */
    public function parse($_data)
    {
        $data = \array_pop($_data);
        $data = \parse_url($data);
        if (isset($data['a'])) {
            $this->_action = \str_replace('/', '\\', $data['a']);
            unset($data['a']);
        }
        if (isset($data['m'])) {
            $this->_method = $data['m'];
            unset($data['m']);
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

    public function display($model)
    {
        echo $model;
    }
}