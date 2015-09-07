<?php
namespace ZPHP\Log;
use ZPHP\Core\Factory as CFactory,
	ZPHP\Core\Config as ZConfig;

class Factory
{
    public static function getInstance($adapter = 'File', $config = null)
    {
    	if(empty($config)) {
    		$config = ZConfig::get('log');
    		if(!empty($config['adapter'])) {
    			$adapter = $config['adapter'];
    		}
    	}
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}
