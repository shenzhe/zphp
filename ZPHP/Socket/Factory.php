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
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}