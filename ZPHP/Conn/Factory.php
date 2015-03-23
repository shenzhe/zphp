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

    /**
     * @param string $adapter
     * @param null $config
     * @return \ZPHP\Conn\IConn
     * @throws \Exception
     */
    public static function getInstance($adapter = "Redis", $config = null)
    {
    	if(empty($config)) {
    		$config = ZConfig::get('connection');
    		if(!empty($config['adapter'])) {
    			$adapter = $config['adapter'];
    		}
    	}

       	$className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return ZFactory::getInstance($className, $config);
    }

}
