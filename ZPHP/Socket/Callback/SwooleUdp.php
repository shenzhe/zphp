<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Conn\Adapter\Swoole;
use ZPHP\Core;


abstract class SwooleUdp extends Swoole
{
    public function onReceive()
    {
        throw new \Exception('udp server must use onPacker');
    }

    abstract public function onPacket($request, $reponse);
}
