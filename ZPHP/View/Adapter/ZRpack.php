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
        $data = gzencode(\json_encode($this->model));
        $pack = new MessagePacker($data);
        $len = strlen($data);
        $pack->writeInt($len+12);
        $pack->writeInt($cmd);
        $pack->writeString($data, $len);
        echo $pack->getData();

    }

}
