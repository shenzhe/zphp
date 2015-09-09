<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */


namespace ZPHP\View\Adapter;
use ZPHP\Protocol\Request;
use ZPHP\Protocol\Response;
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
        if (Request::isHttp()) {
            Response::header("Content-Type", "application/zrpack; charset=utf-8");
        }
        if(Request::isLongServer()) {
            return array(
                $jsonData, $pack->getData()
            );
        }
        echo $pack->getData();
        return null;
    }

}
