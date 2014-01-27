<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */


namespace ZPHP\View\Adapter;
use ZPHP\View\Base,
    ZPHP\Core\Config;

class String extends Base
{
    public function display()
    {
        if (Config::get('server_mode') == 'Http') {
            \header("Content-Type: text/plain; charset=utf-8");
        }
        if (\is_string($this->model)) {
            echo $this->model;
        } else {
            print_r($this->model, true);
        }
    }
}