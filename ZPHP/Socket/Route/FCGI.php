<?php
/**
 * User: shenzhe
 * Date: 2014/2/7
 * 
 *  fcgi方式
 */


namespace ZPHP\Socket\Route;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Fcgi;

class FCGI
{
    private $_client;
    public function run($data)
    {
        if ($this->_client === null) {
            $this->_client = new Fcgi\Client(ZConfig::getField('socket', 'fcgi_host', '127.0.0.1'), ZConfig::getField('socket', 'fcgi_port', 9000));
        }
        try {
            return $this->_client->request($data);
        } catch (\Exception $e) {
            $result =  Formater::exception($e);
            return $result;
        }
    }

}
