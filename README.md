zphp
====

框架重构中，敬请期待！
===================================================

@author: shenzhe (泽泽，半桶水)

@email: shenzhe163@gmail.com

zphp是一个极轻的的，专用于游戏(社交，网页，移动)的服务器端开发框架， 提供高性能实时通信服务解决方案。

根据游戏的特性，框架集成以下功能：

    存储       (ttserver, redis, redis-storage)，
    cache      (apc, memcache, redis, xcache), 
    db         (mysql)，
    队列       (beanstalk, redis)，
    排行榜     (redis)，
    socket     (tcp， react, swoole),
    daemon     (cli模式下，加 -d 即可)

要求：php5.3+， 建议使用php5.4+  (如果使用react做为socket，  必需php5.4+)

特色
======================

1) 性能强悍  （游戏场景中的大部分api可以在5ms内左右处理完）

2）socket, http, rpc 完美融合，自由切换

