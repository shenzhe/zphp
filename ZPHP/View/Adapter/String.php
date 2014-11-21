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
            ZPHP\Common\Utils::header("Content-Type: text/plain; charset=utf-8");
            if (\is_string($this->model)) {
                echo $this->model;
            } else {
                echo json_encode($this->model);
            }

            return null;
        }

        return $this->model;
        
    }
}