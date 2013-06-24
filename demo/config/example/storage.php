<?php

    return array(
        'storage'=>array(
//=========== redis-storage======
            'adapter'=>'RL',
            'name'=>'master',
            'pconnect'=>true,
            'host'=>'127.0.0.1',
            'port'=>6379,
            'timeout'=>5

////========== Redis =========
//            'adapter'=>'Redis',
//            'master'=>array(
//                'name'=>'master',
//                '_prefix'=>'public',
//                'pconnect'=>true,
//                'host'=>'127.0.0.1',
//                'port'=>6379,
//                'timeout'=>5
//            ),
//            'slave'=>array(
//                'name'=>'slave',
//                '_prefix'=>'public',
//                'pconnect'=>true,
//                'host'=>'127.0.0.1',
//                'port'=>6379,
//                'timeout'=>5
//            ),

////============= ttserver ====
//            'adapter'=>'TT',
//            'name'=>'cache',
//            'pconnect'=>true,
//            'servers'=>array(
//                array(
//                    'host'=>'127.0.0.1',
//                    'port'=>11211
//                ),
//                array(
//                    'host'=>'127.0.0.1',
//                    'port'=>11212
//                )
//            )
        ),
    );
