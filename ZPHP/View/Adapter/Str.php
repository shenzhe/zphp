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
    ZPHP\Core\Config;

class Str extends Base
{
    public function display()
    {
        if(Request::isHttp()) {
            Response::header("Content-Type", "text/plain; charset=utf-8");
        }

        if (\is_array($this->model) || \is_object($this->model)) {
            $data =  json_encode($this->model);
        } else {
            $data =  $this->model;
        }
        if(Request::isLongServer()) {
            return $data;
        }

        echo $data;
        return null;
    }
}