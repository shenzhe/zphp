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
        $jsonData = \json_encode($this->model);
        $data = gzencode($jsonData);
        $pack = new MessagePacker();
        $len = strlen($data);
        $pack->writeInt($len+16);
        $pack->writeInt($this->model['cmd']);
        $pack->writeInt($this->model['rid']);
        $pack->writeString($data, $len);
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/zrpack; charset=utf-8");
            echo $pack->getData();
        } else {
            return array(
                $jsonData, $pack->getData()
            );
        } 

    }

}
