<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */
namespace ZPHP\Socket;
use ZPHP\Core\Factory as CFactory,
	ZPHP\Core\Config as ZConfig;

class Factory
{
    public static function getInstance($adapter = 'Swoole', $config=null)
    {
    	if(empty($config)) {
    		$config = ZConfig::get('socket');
    		if(!empty($config['adapter'])) {
    			$adapter = $config['adapter'];
    		}
    	}
        if (is_file(__DIR__ . DS . 'Adapter' . DS . $adapter . '.php')) {
            $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        } else {
            $className = $adapter;
        }
        return CFactory::getInstance($className, $config);
    }
}