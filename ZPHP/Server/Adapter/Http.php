<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Protocol;

class Http
{
    private $_action = 'index';
    private $_method = 'main';
    private $_params = array();

    public function __construct()
    {
        $server = Protocol\Factory::getInstance('Http');
        $server->parse($_REQUEST);
        Core\Route::route($server);
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