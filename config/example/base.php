<?php

    return array(
        'server_mode' => (PHP_SAPI === 'cli') ? 'Cli' : 'Http',                                     //运行模式
        'now_time'=>isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time(),            //系统时间
        'project_name'=>'zphp',                                                                     //项目名
        'app_path'=>'apps',                                                                         //app目录
        'ctrl_path'=>'ctrl'                                                                         //ctrl目录
    );
