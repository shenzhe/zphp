<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 14-5-26
 * Time: 下午12:18
 */

namespace ZPHP\Client\Async;

use ZPHP\Protocol\Request;


class Http
{

    public static function check()
    {
        if (!Request::isLongServer()) {
            throw new \Exception('must long server', -1);
        }
    }

    public static function parseUrl($url)
    {
        $urlInfo = parse_url($url);
        $urlInfo['ssl'] = 0;
        if (empty($urlInfo['port'])) {
            if ('https' === strtolower($urlInfo['scheme'])) {
                $urlInfo['port'] = 443;
                $urlInfo['ssl'] = 1;
            } else {
                $urlInfo['port'] = 80;
            }
        } else {
            if ('https' === strtolower($urlInfo['scheme'])) {
                $urlInfo['ssl'] = 1;
            }
        }

        if (empty($urlInfo['path'])) {
            $urlInfo['path'] = '/';
        }

        return $urlInfo;
    }

    /**
     * @param $url
     * @param $callback
     * @param string $method
     * @param null $data //method==post时, 表示post的数据
     * @param $timeOut //超时时间,单位:ms
     * @param $header //请求头信息
     * @throws \Exception
     */
    public static function getByUrl($url, $callback, $method = 'GET', $data = null, $timeOut = 15000, $header = [])
    {
        self::check();
        $urlInfo = self::parseUrl($url);
        $method = strtoupper($method);
        $urlInfo['method'] = $method;
        $urlInfo['data'] = $data;
        \swoole_async_dns_lookup($urlInfo['host'], function ($host, $ip) use ($urlInfo, $callback, $timeOut, $header) {
            if ('GET' == $urlInfo['method']) {
                self::getByIp($ip, $urlInfo['port'], $urlInfo['ssl'], $urlInfo['path'], $callback, $timeOut, $header, $host);
            } else if ('POST' == $urlInfo['method']) {
                self::postByIp($ip, $urlInfo['port'], $urlInfo['ssl'], $urlInfo['path'], $urlInfo['data'], $callback, $timeOut, $header, $host);
            } else {
                throw new \Exception($urlInfo['method'] . ' method no support', -1);
            }
        });
    }

    /**
     * @param $ip //目标地址ip
     * @param $port //目标地址端口
     * @param $ssl //是否ssl
     * @param $path //请求路径
     * @param $callback //请求完成之后的回调函数
     * @param $timeOut //超时时间,单位:ms
     * @param $header //请求头信息
     * @param null $host //host地址
     */
    public static function getByIp($ip, $port, $ssl, $path, $callback, $timeOut = 15000, $header = [], $host = null)
    {
        self::check();
        $cli = new \swoole_http_client($ip, $port, $ssl);
        $cli->setHeaders($header + [
                'Host' => $host ? $host : $ip,
                "User-Agent" => 'ZPHP-ASYNCHTTPCLIENT-' . \ZPHP\ZPHP::VERSION,
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip',
            ]);
        $timeId = \swoole_timer_after($timeOut, function () use ($cli, $callback) {
            $cli->close();
            if (is_callable($callback)) {
                $callback(null, 1);
            }
        });
        $cli->get($path, function ($cli) use ($timeId, $callback) {
            \swoole_timer_clear($timeId);
            $cli->close();
            if (is_callable($callback)) {
                $callback($cli);
            }
        });
    }

    /**
     * @param $ip //目标地址ip
     * @param $port //目标地址端口
     * @param $ssl //是否ssl
     * @param $path //请求路径
     * @param $data //请求的post数据
     * @param $callback //请求完成之后的回调函数
     * @param $timeOut //超时时间,单位:ms
     * @param $header //头信息
     * @param null $host //host地址
     */
    public static function postByIp($ip, $port, $ssl, $path, $data, $callback, $timeOut = 15000, $header = [], $host = null)
    {
        self::check();
        $cli = new \swoole_http_client($ip, $port, $ssl);
        $cli->setHeaders($header + [
                'Host' => $host ? $host : $ip,
                "User-Agent" => 'ZPHP-ASYNCHTTPCLIENT-' . \ZPHP\ZPHP::VERSION,
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip',
            ]);
        $timeId = \swoole_timer_after($timeOut, function () use ($cli, $callback) {
            $cli->close();
            if (is_callable($callback)) {
                $callback($cli, 1);
            }
        });
        $cli->post($path, $data, function ($cli) use ($timeId, $callback) {

            \swoole_timer_clear($timeId);
            $cli->close();
            if (is_callable($callback)) {
                $callback($cli);
            }
        });
    }
}