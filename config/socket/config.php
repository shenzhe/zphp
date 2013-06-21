<?php

    return array(
        'server_mode' => 'Socket',
        'project_name'=>'zphp',
        'app_path'=>'apps',
        'ctrl_path'=>'ctrl',
        'socket'=>array(
            'host'=>'0.0.0.0',                          //socket 监听ip
            'port'=>8991,                             //socket 监听端口
            'adapter'=>'React',                          //socket 驱动模块
            'daemonize'=>0,                             //是否开启守护进程
            'times'=>array(),                           //定时服务
            'params'=>array(),                          //其它参数
            'client_class'=>'socket\\React',            //socket 回调类
            'protocol'=>'Json',                         //socket通信数据协议
            'call_mode'=>'ZPHP'                         //业务处理模式
        ),
        
    );
