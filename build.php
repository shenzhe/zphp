<?php
    require 'ZPHP/Common/Dir.php';
    echo "pls enter app path:".PHP_EOL;
    $app_path = trim(fgets(STDIN));

    if(is_dir($app_path)) {
        echo 'dir is exist, continue~~ pls input Y or N'.PHP_EOL;
        $ret = trim(fgets(STDIN));
        if('y' !== strtolower($ret)) {
            return;
        }
    }


    if(ZPHP\Common\Dir::make($app_path)) {
        $dirList = array(
            'apps'.DIRECTORY_SEPARATOR.'ctrl'.DIRECTORY_SEPARATOR.'main',
            'apps'.DIRECTORY_SEPARATOR.'entity',
            'apps'.DIRECTORY_SEPARATOR.'dao',
            'apps'.DIRECTORY_SEPARATOR.'service',
            'apps'.DIRECTORY_SEPARATOR.'common',
            'config'.DIRECTORY_SEPARATOR.'default',
            'config'.DIRECTORY_SEPARATOR.'public',
            'template',
            'webroot'.DIRECTORY_SEPARATOR.'static',
        );
        foreach($dirList as $realPath) {
            if(ZPHP\Common\Dir::make($app_path.DIRECTORY_SEPARATOR.$realPath)) {
                echo $realPath."  done...".PHP_EOL;
            }
        }
        $mainTxt = '<?php
use ZPHP\ZPHP;
$rootPath = dirname(__DIR__);
require \''.__DIR__.'\'. DIRECTORY_SEPARATOR . \'ZPHP\' . DIRECTORY_SEPARATOR . \'ZPHP.php\';
ZPHP::run($rootPath);';
        file_put_contents($app_path.DIRECTORY_SEPARATOR.'webroot'.DIRECTORY_SEPARATOR.'main.php', $mainTxt);
        echo "main.php done...".PHP_EOL;

        $ctrlTxt = '<?php
namespace ctrl\main;
use ZPHP\Controller\IController,
    ZPHP\Core\Config,
    ZPHP\View;
use ZPHP\Protocol\Request;

class main implements IController
{

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
        $project = Config::getField(\'project\', \'name\', \'zphp\');
        $data = $project." runing!\n";
        $params = Request::getParams();
        if(!empty($params)) {
            foreach($params as $key=>$val) {
                $data.= "key:{$key}=>{$val}\n";
            }
        }
        return $data;
    }
}

';
		file_put_contents($app_path.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'ctrl'.DIRECTORY_SEPARATOR.'main'.DIRECTORY_SEPARATOR.'main.php', $ctrlTxt);
        echo "ctrl done...".PHP_EOL;

        echo "pls enter project_name:".PHP_EOL;
    	$project_name = trim(fgets(STDIN));

       	$configTxt = '<?php

    return array(
        \'server_mode\' => (PHP_SAPI === \'cli\') ? \'Cli\' : \'Http\',
        \'app_path\'=>\'apps\',
        \'ctrl_path\'=>\'ctrl\',
        \'project\'=>array(
            \'name\'=>\''.$project_name.'\',                 
        	\'view_mode\'=>\'Str\',   		
        	\'ctrl_name\'=>\'a\',				
        	\'method_name\'=>\'m\',				
        )
    );
';
		file_put_contents($app_path.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'default'.DIRECTORY_SEPARATOR.'config.php', $configTxt);
        echo "config done...".PHP_EOL;
        echo "finish...".PHP_EOL;
    }


    return;