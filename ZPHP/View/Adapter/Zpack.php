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

class Zpack extends Base
{
    public function display()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/zpack; charset=utf-8");
        }

        $pack = new MessagePacker();
        $pack->writeString(json_encode($this->model));
        echo $pack->getData();

    }


}
