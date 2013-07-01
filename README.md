zphp
===================================================

@author: shenzhe (泽泽，半桶水)

@email: shenzhe163@gmail.com

zphp是一个极轻的的，专用于游戏(社交，网页，移动)的服务器端开发框架， 提供高性能实时通信服务解决方案。

特色
======================

    1) 性能强悍     (大部分api可以在10ms内处理完)
    2) socket, http, rpc 完美融合，自由切换
    3) 通信协议自由扩展    
    4) 可配置的自由的view层
    5) 丰富的kv持久存储支持    (ttserver, redis, redis-storage)，
    6) 丰富的cache      (apc, memcached, redis, xcache, yac),
    7) 队列支持       (beanstalk, redis)，
    8) 实时排行榜支持     (redis)，
    9) 多进程支持        (pcntl)

TODO
========

    1) 多进程模块：类ph-fpm的进程管理 (处理一定的请求之后，自杀，然后重启一个新进程)
    2) 多线程模块
    3) 完善的守护进程实现
    4) 定时器完善

相关扩展
=======
    socket： 编译选项加上: --enable-pcntl --enable-sockets --enable-sysvmsg
    memcached：http://pecl.php.net/package/memcached
    redis: https://github.com/shenzhe/phpredis
    swoole: https://github.com/matyhtf/php_swoole   (高性能socket模块)
    xcache: https://github.com/lighttpd/xcache      (php opcode代码加速模块)
    yac: https://github.com/laruence/yac            (基于共享内存的高性能 key=>val cache)
    yar: https://github.com/laruence/yar            (rpc框架)


流程图
=======
![点击查看zphp流程图](https://github.com/shenzhe/zphp/blob/master/zphp_jg.jpg "zphp流程图") 

运行demo
========
    http模式：
        1) 域名绑定到目录webroot
        2) 运行：http://域名/main.php?name=zphp&k1=v1

    socket模块:
        1) php 项目目录/webroot/main.php socket
        2) telnet 127.0.0.1 8991
        3) 输入: {"a":"main\main",name":"zphp","k1":"v1"} 发送
        4) 返回: zphp running\n

约定
===========
    config/example/base.php 里的配置项目是必需的。
    当不同模块使用了相同的adapter(如:redis), 如用到不同的host或port,可配置_prefix进行隔离
    cli模块式 php 项目目录/webroot/main.php 配置目录名(如:default) 参数(格式：a=b\&c=d\&e=f)



