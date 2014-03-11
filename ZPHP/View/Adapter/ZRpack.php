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
        $data = gzencode(\json_encode($this->model));
        $cmd = $data['cmd'];
        unset($data['cmd']);
        $pack = new MessagePacker();
        $len = strlen($data);
        $pack->writeInt($len+12);
        $pack->writeInt($cmd);
        $pack->writeString($data, $len);
        echo $pack->getData();

    }

}
