##ZPHP
ZPHP是一个极轻的的，专用于游戏(社交，网页，移动)的服务器端开发框架， 提供高性能实时通信服务解决方案。

###发起人
* shenzhe (泽泽，半桶水) / shenzhe163@gmail.com

###维护者
* cooper [https://github.com/huanghua581](https://github.com/huanghua581)


##特色

    1) 性能强悍 (大部分api可以在10ms内处理完)
    2) socket, http, rpc 完美融合，自由切换
    3) 通信协议自由扩展 
    4) 可配置的自由的view层
    5) 丰富的kv持久存储支持 (ttserver, redis, redis-storage)
    6) 丰富的cache (apc, memcached, redis, xcache, yac)
    7) 队列支持  (beanstalk, redis)
    8) 实时排行榜支持 (redis)
    9) 多进程支持 (pcntl, 类ph-fpm的进程管理 (处理一定的请求之后自动kill，然后master会fork一个新进程))
    10) 多线程支持
    11) composer 安装

##TODO

    1) 完善的守护进程实现
    2) 定时器完善

##相关扩展

* socket： 编译选项加上: --enable-pcntl --enable-sockets --enable-sysvmsg
* 多线程：https://github.com/krakjoe/pthreads
* 异步：http://pecl.php.net/package/libevent (用react做socket推荐用此扩展)
* swoole: https://github.com/matyhtf/php_swoole  (高性能socket模块)
* redis: https://github.com/shenzhe/phpredis
* xcache: https://github.com/lighttpd/xcache (php opcode代码加速模块)
* yac: https://github.com/laruence/yac (基于共享内存的高性能 key=>val cache)
* yar: https://github.com/laruence/yar  (rpc框架)

##流程图
![点击查看zphp流程图](https://raw.github.com/shenzhe/zphp/master/zphp_jg.jpg "zphp流程图") 

##安装

普通使用请参照demo文件夹。

###composer 安装
1.创建composer.json文件   
2.添加代码  
```javascript
{
    "require": {
        "zphp/zphp": "dev-master"
    }
}
```  
3.执行composer install  
4.然后参照demo_composer文件夹初始项目。

##运行demo

    http模式：
    	1) 域名绑定到目录webroot
    	2) 运行：http://域名/main.php?name=zphp&k1=v1
    socket模块:
    	1) php 项目目录/webroot/main.php socket
    	2) telnet 127.0.0.1 8991
    	3) 输入: {"a":"main\main",name":"zphp","k1":"v1"} 发送
    	4) 返回: zphp running\n
        

##约定
    config/example/base.php 里的配置项目是必需的。
    当不同模块使用了相同的adapter(如:redis), 如用到不同的host或port,可配置_prefix进行隔离
    cli模块执行 php 项目目录/webroot/main.php 配置目录名(如:default) 参数(格式：a=b\&c=d\&e=f)
    
##环境要求
PHP >= 5.3.7

##协议

MIT license
