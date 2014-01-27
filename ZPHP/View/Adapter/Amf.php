<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */


namespace ZPHP\View\Adapter;
use ZPHP\View\Base,
    ZPHP\Core\Config;

class Amf extends Base
{
    public function display()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/amf; charset=utf-8");
        }
        echo \amf3_encode($this->model);
    }
}