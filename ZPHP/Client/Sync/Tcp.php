<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/10/31
 * Time: 10:49
 */

namespace ZPHP\Client\Sync;

use ZPHP\Core\Config;

class Tcp
{
    private static $clients;

    /**
     * @param $ip
     * @param $port
     * @return \swoole_client
     */
    public static function singleton($ip, $port)
    {
        $key = "{$ip}:{$port}";
        if (empty(self::$clients[$key])) {
            self::$clients[$key] = new Tcp($ip, $port);
        }
        return self::$clients[$key];
    }

    /**
     * Tcp constructor.
     * @param $ip
     * @param $port
     * @throws \Exception
     */
    public function __construct($ip, $port)
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $client->connect($ip, $port);
        $config = [
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 4,       //第几个字节开始计算长度
            'package_max_length' => 2000000,  //协议最大长度
            'ctrl_name' => 'a',
            'method_name' => 'm',
        ];
        $socketConfig = Config::get('socket');
        if (!empty($socketConfig)) {
            foreach ($config as $key => &$val) {
                if (isset($socketConfig[$key])) {
                    $val = $socketConfig[$key];
                }
            }
        }
        unset($val);
        $client->set($config);
        return $client;
    }

}