<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Server\Adapter;
use ZPHP\Core,
    ZPHP\Protocol;

class Rpc
{
    private $_action = 'index';
    private $_method = 'main';
    private $_params = array();

    public function run()
    {
        $rpc = new \Yar_Server(new __CLASS__);
        $rpc->handle();
    }

    public function api($params)
    {
        $server = Protocol\Factory::getInstance('Rpc');
        $server->parse($params);
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