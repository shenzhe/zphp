<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 *  
 */
namespace ZPHP\View;

abstract class Base implements IView
{

    protected $model;

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    //数据输出
    abstract public function display();

    public function render()
    {
        \ob_start();
        $this->display();
        $content = \ob_get_contents();
        \ob_end_clean();
        return $content;
    }

}