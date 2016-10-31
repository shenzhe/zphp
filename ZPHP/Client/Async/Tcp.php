<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/10/19
 * Time: 12:18
 */

namespace ZPHP\Client\Async;

use ZPHP\Protocol\Request;

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

    public function __construct($ip, $port)
    {
        if (!Request::isLongServer()) {
            throw new \Exception('must long server', -1);
        }

        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $client->connect($ip, $port);
        return $client;
    }

}