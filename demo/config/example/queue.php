<?php

    return array(
        'rank'=>array(
            'type'=>'Redis',
            'name'=>'queue',
            'pconnect'=>true,
            'host'=>'127.0.0.1',
            'port'=>6379,
            'timeout'=>5
//            'type'=>'Beanstalk',
//            'name'=>'cache',
//            'pconnect'=>true,
//            'servers'=>array(
//                array(
//                    'host'=>'127.0.0.1',
//                    'port'=>3772
//                ),
//                array(
//                    'host'=>'127.0.0.1',
//                    'port'=>3773
//                )
//            )
        )
    );
