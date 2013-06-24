<?php
namespace ctrl\main;
use ZPHP\Controller\IController,
    ZPHP\Core\Config,
    ZPHP\View;

class main implements IController
{
    private $_server;
    public function setServer($server)
    {
        $this->_server = $server;
    }

    public function _before()
    {
        return true;
    }

    public function _after()
    {
        //
    }

    public function main()
    {
        $project = Config::get('project_name', 'zphp');
        $model = $project." runing!\n";
        $params = $this->_server->getParams();
        if(!empty($params)) {
            foreach($params as $key=>$val) {
                $model.= "key:{$key}=>{$val}\n";
            }
        }
        $viewMode = Config::get('view_mode', 'String');
        $view = View\Factory::getInstance($viewMode);
        $view->setModel($model);
        return $view;
    }
}

