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
            'call_mode'=>'ZPHP',                         //业务处理模式,ZPHP:内置route, RPC: rpc方式, FASTCGI: fastcig方式
            'work_mode'=>3,                             //工作模式：1：单进程单线程 2：多线程 3： 多进程
            'worker_num'=>3,                                 //工作进程数
            'max_request'=>1000,                            //单个进程最大处理请求数
        ),

        'queue'=>array(
            'adapter'=>'Php',
            'key'=>ftok(__FILE__, 'a'),
        )
        
    );
