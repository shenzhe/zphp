<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * Json view
 */


namespace ZPHP\View\Adapter;
use ZPHP,
    ZPHP\View\Base;

class Php extends Base
{
    private $tplFile;

    public function setTpl($tpl)
    {
        $this->tplFile = $tpl;
    }

    public function output()
    {
        $tplPath = ZPHP\Core\Config::get('tpl_path');
        $fileName = ZPHP\ZPHP::getRootPath() . DS . $tplPath . DS . $this->tplFile;
        if (!\is_file($fileName)) {
            throw new \Exception("no file {$fileName}");
        }
        if (!empty($this->model)) {
            \extract($this->model);
        }
        include "{$fileName}";
    }

    public function display()
    {
        \header("Content-Type: text/html; charset=utf-8");
        $this->output();
    }


}