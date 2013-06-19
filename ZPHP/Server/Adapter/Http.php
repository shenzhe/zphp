<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core;
class Http
{
    private $_action = 'index';
    private $_method = 'main';
    private $_params = array();

    public function __construct()
    {
        $data = $_REQUEST;

        if(isset($data['a'])) {
            $this->_action = \str_replace('/', '\\', $data['a']);
            unset($data['a']);
        }
        if(isset($data['m'])) {
            $this->_method = $data['m'];
            unset($data['m']);
        }
        $this->_params = $data;
        Core\Route::route($this);
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