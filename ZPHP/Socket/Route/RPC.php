<?php
/**
 * User: shenzhe
 * Date: 2014/2/7
 * 
 *  rpc方式，需扩展支持：https://github.com/laruence/yar
 */


namespace ZPHP\Socket\Route;
use ZPHP\Core\Config as ZConfig;

class RPC
{
    private $_rpc;
    public function run($data, $fd=null)
    {
        if ($this->_rpc === null) {
            $this->_rpc = new \Yar_Client(ZConfig::getField('socket', 'rpc_host'));
        }
        return $this->_rpc->api($data);
        
    }

}
