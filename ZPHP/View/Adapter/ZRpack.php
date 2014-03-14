<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */


namespace ZPHP\View\Adapter;
use ZPHP\View\Base,
    ZPHP\Core\Config,
    ZPHP\Common\MessagePacker;;

class ZRpack extends Base
{
    public function display()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/zrpack; charset=utf-8");
        }
        $cmd = $this->model['cmd'];
        unset($this->model['cmd']);
        $rid = $this->model['rid'];
        unset($this->model['rid']);
        $data = gzencode(\json_encode($this->model));
        $pack = new MessagePacker();
        $len = strlen($data);
        $pack->writeInt($len+16);
        $pack->writeInt($cmd);
        $pack->writeInt($rid);
        $pack->writeString($data, $len);
        echo $pack->getData();

    }

}
