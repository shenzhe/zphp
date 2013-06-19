<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Manager;
class Beanstalk
{
    private static $instances;

    public static function getInstance($config)
    {
        $name = $config['name'];
        if (empty(self::$instance[$name])) {
            $beanstalk = new \Beanstalk();
            foreach ($config['servers'] as $server) {
                $beanstalk->addServer($server['host'], $server['port']);
            }
            self::$instances[$name] = $beanstalk;
        }
        return self::$instances[$name];
    }
}