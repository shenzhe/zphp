<?php

    return array(
        'server_mode' => 'Socket',
        'project_name'=>'zphp',
        'app_path'=>'apps',
        'socket'=>array(
            'host'=>'0.0.0.0',                          //socket 监听ip
            'port'=>'8888',                             //socket 监听端口
            'adpter'=>'React',                          //socket 驱动模块
            'daemonize'=>0,                             //是否开启守护进程
            'times'=>array(),                           //定时服务
            'params'=>array(),                          //其它参数
            'client_class'=>'socket\\React',            //socket 回调类
            'protocol'=>'Json',                         //socket通信数据协议
            'call_mode'=>'ZPHP'                         //业务处理模式
        ),
        'pdo'=>array(
            'dns'=>'mysql:host=localhost;port=3306',
            'user'=>'zphp',
            'pass'=>'zphp',
            'dbname'=>'zphp',
            'chartset'=>'UTF8',
        ),
        'storage'=>array(
            'RL'=>array(
                'name'=>'master',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>'6379',
                'timeout'=>5
            )
        ),
        'cache'=>array(
            'Yac'=>[]
        ),
        'rank'=>array(
            'Redis'=>array(
                'name'=>'rank',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>'6379',
                'timeout'=>5
            )
        ),
        'queue'=>array(
            'Redis'=>array(
                'name'=>'queue',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>'6379',
                'timeout'=>5
            )
        ),
    );
