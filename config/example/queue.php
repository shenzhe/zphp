<?php

    return array(
        'Redis'=>array(
            'name'=>'queue',
            'pconnect'=>true,
            'host'=>'127.0.0.1',
            'port'=>6379,
            'timeout'=>5
        ),
        'Beanstakl'=>array(
            'name'=>'cache',
            'pconnect'=>true,
            'servers'=>array(
                array(
                    'host'=>'127.0.0.1',
                    'port'=>3772
                ),
                array(
                    'host'=>'127.0.0.1',
                    'port'=>3773
                )
            )
        )
    );
