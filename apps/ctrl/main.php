<?php
namespace apps\ctrl;
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
//        echo "before call\n";
        return true;
    }

    public function _after()
    {
//        echo "after call\n";
    }

    public function main()
    {
//        print_r($this->_server->getParams());
        $project = Config::get('project_name');
        $view = View\Factory::getInstance('String');
        $view->setModel($project." runing!\n");
        return $view;
    }
}

