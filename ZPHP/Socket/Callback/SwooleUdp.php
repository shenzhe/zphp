<?php


namespace ZPHP\Socket\Callback;



abstract class SwooleUdp extends Swoole
{
    public function onReceive()
    {
        throw new \Exception('udp server must use onPacker');
    }

    abstract public function onPacket($serv, $data, $clientInfo);
}
