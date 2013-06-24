<?php

    return array(
        'cache'=>array(
            'locale'=>array(            //本机共享内存缓存
                'adapter'=>'Yac',       // Yac, Apc, Xcache
            ),
            'net'=>array(               //网络分布式缓存
                'adapter'=>'Redis',
                '_prefix'=>'public',
                'name'=>'cache',
                'pconnect'=>true,
                'host'=>'127.0.0.1',
                'port'=>6379,
                'timeout'=>5
            ),
//            'net'=>array(
//                'adapter'=>'Memcached',
//                'name'=>'cache',
//                'pconnect'=>true,
//                'servers'=>array(
//                    array(
//                        'host'=>'127.0.0.1',
//                        'port'=>11211
//                    ),
//                    array(
//                        'host'=>'127.0.0.1',
//                        'port'=>11212
//                    )
//                )
//            )
        ),
    );
