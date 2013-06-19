<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Protocol\Adapter;
use ZPHP\Core;
use ZPHP\Protocol\IProtocol;

class Http implements IProtocol
{
    private $_action = 'index';
    private $_method = 'main';
    private $_params = array();

    public function parse($data)
    {
        if (isset($data['a'])) {
            $this->_action = \str_replace('/', '\\', $data['a']);
            unset($data['a']);
        }
        if (isset($data['m'])) {
            $this->_method = $data['m'];
            unset($data['m']);
        }
        $this->_params = $data;
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