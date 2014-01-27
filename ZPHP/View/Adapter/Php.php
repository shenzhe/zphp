<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 * 
 */


namespace ZPHP\View\Adapter;
use ZPHP,
    ZPHP\View\Base,
    ZPHP\Core\Config;

class Php extends Base
{
    private $tplFile;

    public function setTpl($tpl)
    {
        $this->tplFile = $tpl;
    }

    public function display()
    {
        $tplPath = ZPHP\Core\Config::getField('project', 'tpl_path', 'template' . DS . 'template');
        $fileName = ZPHP\ZPHP::getRootPath() . DS . $tplPath . DS . $this->tplFile;
        if (!\is_file($fileName)) {
            throw new \Exception("no file {$fileName}");
        }
        if (!empty($this->model)) {
            \extract($this->model);
        }
        include "{$fileName}";
    }


}
