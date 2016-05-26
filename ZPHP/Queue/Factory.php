<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 对列接口
 */
namespace ZPHP\Queue;
use ZPHP\Core\Factory as CFactory,
	ZPHP\Core\Config as ZConfig;

class Factory
{
	/**
	 * @param string $adapter
	 * @param null $config
	 * @return IQueue
	 * @throws \Exception
	 */
    public static function getInstance($adapter = 'Redis', $config = null)
    {
    	if(empty($config)) {
    		$config = ZConfig::get('queue');
    		if(!empty($config['adapter'])) {
    			$adapter = $config['adapter'];
    		}
    	}
        $className = __NAMESPACE__ . "\\Adapter\\{$adapter}";
        return CFactory::getInstance($className, $config);
    }
}
