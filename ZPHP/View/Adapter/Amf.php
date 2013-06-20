<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * Json view
 */


namespace ZPHP\View\Adapter;
use ZPHP\View\Base;

class Amf extends Base
{
    public function output()
    {
        \header("Content-Type: application/amf; charset=utf-8");
        return \amf3_encode($this->model);
    }
}