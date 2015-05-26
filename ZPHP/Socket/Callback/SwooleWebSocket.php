<?php


namespace ZPHP\Socket\Callback;

use ZPHP\Conn\Adapter\Swoole;
use ZPHP\Socket\ICallback;
use ZPHP\Core\Config as ZConfig;
use ZPHP\Core;
use ZPHP\View\Factory as ZView;


class SwooleWebSocket extends SwooleHttp
{


    protected $protocolName = 'Json';

    private $buffer = [];

    public function onOpen($serv, $request)
    {
        if(isset($this->buffer[$request->fd])) {
            unset($this->buffer[$request->fd]);
        }
    }

    public function onMessage($serv, $frame)
    {
        if(empty($frame->finish)) {
            if(empty($this->buffer[$frame->fd])) {
                $this->buffer[$frame->fd] = $frame->data;
            } else {
                $this->buffer[$frame->fd].= $frame->data;
            }
            return ;
        }
        if(!empty($this->buffer[$frame->fd])) {
            $frame->data = $this->buffer[$frame->fd].$frame->data;
            unset($this->buffer[$frame->fd]);
        }
        if(empty($frame->data)) return;
        $this->protocol->parse($frame->data);
        $result =  Core\Route::route($this->protocol);
        if(is_null($result)) {
            return ;
        }
        return $frame->push($result);

    }

    public function onHandShake($request, $response)
    {

    }
}
