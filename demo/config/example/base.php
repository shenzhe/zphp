<?php

    return array(
        'server_mode' => (PHP_SAPI === 'cli') ? 'Cli' : 'Http',                                     //运行模式
        'now_time'=>isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time(),            //系统时间
        'app_path'=>'apps',                                                                         //app目录
        'ctrl_path'=>'ctrl',                                                                         //ctrl目录
        'log_path'=>'log',                                                                           //日志目录
        'time_zone'=>'Asia/Shanghai',                                                                //时区
        'lib_path'=>'lib',                                                                           //公共lib库地址(与ZPHP同级目录)
    );
