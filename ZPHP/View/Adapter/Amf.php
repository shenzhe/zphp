<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * Json view
 */


namespace ZPHP\View\Adapter;
use ZPHP\View\Base,
    ZPHP\Core\Config;

class Amf extends Base
{
    public function output()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: application/amf; charset=utf-8");
        }
        return \amf3_encode($this->model);
    }
}