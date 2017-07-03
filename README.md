## ZPHP

ZPHP是一个极轻的的，定位于后置SOA服务的框架，可开发独立高效的长驻服务，并能适应多端的变化。

### 发起人

* shenzhe (泽泽，半桶水) / shenzhe163@gmail.com

### 维护者

* godsoul [www.osfans.org(godsoul1986@gmail.com)
* cooper [https://github.com/huanghua581](https://github.com/huanghua581)
* yongchuan (charles) / charles.m1256@gmail.com
* ruanxianhuo  https://github.com/asdf20122012 ruanxianhuo@126.com


## 特色

1) 性能强悍 (大部分api可以在10ms内处理完)
2) socket, http, rpc 完美融合，自由切换
3) 通信协议自由扩展 
4) 可配置的自由的view层
5) 丰富的kv持久存储支持 (ttserver, redis, redis-storage)
6) 丰富的cache (apc, memcached, redis, xcache, yac)
7) 队列支持  (beanstalk, redis)
8) 实时排行榜支持 (redis)
9) 多进程支持 (pcntl, 类ph-fpm的进程管理 (处理一定的请求之后自动kill，然后master会fork一个新进程))
10) 多线程支持 (no swoole, need pthreads extension)
11) composer 安装

## demo

* 地址： [https://github.com/shenzhe/zphpdemo](https://github.com/shenzhe/zphpdemo)

## 相关扩展

*生产环境推荐：

1) https://github.com/matyhtf/swoole  (高性能socket模块)
2）https://github.com/shenzhe/phpredis    (redis，用于cache,conn等)

*使用react做socket，需要：

1) 编译选项加上: --enable-pcntl --enable-sockets
2) http://pecl.php.net/package/libevent   (libevent库)
3) https://github.com/krakjoe/pthreads     (多线程支持，可选)

## 流程图

![点击查看zphp流程图](https://raw.github.com/shenzhe/zphp/master/zphp_jg.jpg "zphp流程图") 

## 约定

- config/example/base.php 里的配置项目是必需的。
- 当不同模块使用了相同的adapter(如:redis), 如用到不同的host或port,可配置_prefix进行隔离
- cli模块执行 php 项目目录/webroot/main.php 配置目录名(如:default) 参数(格式：a=b\&c=d\&e=f)
    
## 环境要求

PHP >= 5.4

## 协议

MIT license
