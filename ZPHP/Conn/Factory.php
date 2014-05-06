<?php

namespace ZPHP\Conn;

use ZPHP\Core\Factory as ZFactory,
	ZPHP\Core\Config as ZConfig;
/**
 * connect处理工厂
 *
 */
class Factory
{


    public static function getInstance($adapter = "Redis", $config = null)
    {
    	if(empty($config)) {
    		$config = ZConfig::get('conn');
    		if(!empty($config['adapter'])) {
    			$adapter = $config['adapter'];
    		}
    	}

       	$className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return ZFactory::getInstance($className, $config);
    }

}
