<?php

    return array(
        'server_mode' => (PHP_SAPI === 'cli') ? 'Cli' : 'Http',
        'app_path'=>'apps',
        'ctrl_path'=>'ctrl',
        'project'=>array(
            'name'=>'zphp',                 //项目名称。(会做为前缀，隔离不同的项目)
        	'view_mode'=>'String',   		//view模式
        	'action_name'=>'a',				//ctrl参数名
        	'method_name'=>'m',				//method参数名    http://host/?{action_name}=main\main&{method_name}=main
        )
    );
