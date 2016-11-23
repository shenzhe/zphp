<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/11/21
 * Time: 11:38
 */

namespace ZPHP\Client\Monitor;

use ZPHP\Client\Rpc\Udp;
use ZPHP\Core\Config as ZConfig;


class Client
{

    /**
     * @param $api
     * @param $time
     * @desc 服务方耗时上报
     */
    public static function serviceDot($api, $time)
    {
        $config = ZConfig::get('monitor');
        if (empty($config)) {
            return;
        }
        $client = new Udp($config['host'], $config['port'], $config['timeOut']);
        $client->setApi('dot')->call('service',
            [
                'api' => $api,
                'time' => $time
            ]
        );
    }

    /**
     * @param $api
     * @param $time
     * @desc 调用方耗时上线
     */
    public static function clientDot($api, $time)
    {
        $config = ZConfig::get('monitor');
        if (empty($config)) {
            return;
        }
        $client = new Udp($config['host'], $config['port'], $config['timeOut']);
        $client->setApi('dot')->call('client',
            [
                'api' => $api,
                'time' => $time
            ]
        );
    }

    /**
     * @param $api
     * @param $time
     * @desc task任务耗时
     */
    public static function taskDot($api, $time)
    {
        $config = ZConfig::get('monitor');
        if (empty($config)) {
            return;
        }
        $client = new Udp($config['host'], $config['port'], $config['timeOut']);
        $client->setApi('dot')->call('task',
            [
                'api' => $api,
                'time' => $time
            ]
        );
    }
}