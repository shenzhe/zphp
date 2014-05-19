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
        $tplPath = ZPHP\Core\Config::getField('project', 'tpl_path', ZPHP\ZPHP::getRootPath() . DS  . 'template' . DS . 'default'. DS);
        $fileName = $tplPath . $this->tplFile;
        if (!\is_file($fileName)) {
            throw new \Exception("no file {$this->tplFile}");
        }
        if (!empty($this->model)) {
            \extract($this->model);
        }
        include "{$fileName}";
    }


}
