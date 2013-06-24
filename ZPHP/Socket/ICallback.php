<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  view接口
 */
namespace ZPHP\Socket;
interface ICallback
{
    public function onStart();

    public function onConnect();

    public function onReceive();

    public function onClose();

    public function onShutdown();
}