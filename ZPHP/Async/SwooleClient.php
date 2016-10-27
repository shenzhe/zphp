<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/10/19
 * Time: 12:18
 */

namespace ZPHP\Async;


use ZPHP\Protocol\Request;

class SwooleClient
{
    private static $client;
    public static function init($ip, $port, $sendFunc, $recvFunc)
    {
        if(!Request::isLongServer()) {
            throw new \Exception('must long server', -1);
        }
        self::$client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        self::$client->connect($ip, $port);
        self::$client->on('connect', function($cli) use ($sendFunc) {
            $sendFunc($cli);
        });

        self::$client->on('receive', function($cli, $data) use ($recvFunc) {
            $recvFunc($cli, $data);
        });

    }

}