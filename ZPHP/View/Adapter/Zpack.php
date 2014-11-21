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
        $pack = new MessagePacker();
        $pack->writeString(json_encode($this->model));
        if (Config::get('server_mode') == 'Http') {
            ZPHP\Common\Utils::header("Content-Type: application/zpack; charset=utf-8");
            echo $pack->getData();
        } else {
            return array($this->model, $pack->getData);
        }
        

    }


}
