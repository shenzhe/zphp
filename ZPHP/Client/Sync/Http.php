<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/10/31
 * Time: 10:41
 */

namespace ZPHP\Client\Sync;


class Http
{
    /**
     * @var curl_init
     */
    public static $ch;

    public static function init()
    {
        if (empty(self::$ch)) {
            self::$ch = \curl_init();
        }
    }

    /**
     * @param $url //目标地址
     * @param $callback //回调函数
     * @param string $method //请求method
     * @param null $data //method==post时, 表示post的数据
     * @param int $timeOut //超时时间,单位:ms
     * @param array $header //请求头信息
     * @param int $needHeader //是否需要请求头信息
     * @return mixed
     * @throws \Exception
     */
    public static function getByUrl($url, $callback = null, $method = 'GET', $data = null, $timeOut = 15000, $header = [], $needHeader = 0)
    {
        self::init();
        curl_setopt(self::$ch, CURLOPT_HEADER, $needHeader);
        curl_setopt(self::$ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt(self::$ch, CURLOPT_CONNECTTIMEOUT_MS, $timeOut);
        curl_setopt(self::$ch, CURLOPT_TIMEOUT_MS, $timeOut);
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt(self::$ch, CURLOPT_REFERER, $url);
        if ('post' === strtolower($method)) {
            curl_setopt(self::$ch, CURLOPT_POST, true);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt(self::$ch, CURLOPT_URL, $url);
        } else {
            if (!empty($data)) {
                if (is_array($data)) {
                    $dataStr = http_build_query($data);
                } else {
                    $dataStr = $data;
                }
                if (strpos($url, '?')) {
                    curl_setopt(self::$ch, CURLOPT_URL, $url . '&' . $dataStr);
                } else {
                    curl_setopt(self::$ch, CURLOPT_URL, $url . '?' . $dataStr);
                }
            } else {
                curl_setopt(self::$ch, CURLOPT_URL, $url);
            }
        }

        if (empty($header['User-Agent'])) {
            curl_setopt(self::$ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:13.0) Gecko/20100101 Firefox/13.0.1');
        }

        $headers = [];
        if (!empty($header)) {
            $headers = array_merge($headers, $header);
        }

        curl_setopt(self::$ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec(self::$ch);
        if (empty($response)) {
            $no = curl_errno(self::$ch);
            if ($no) {
                throw new \Exception(curl_error(self::$ch), -1);
            }
        }

        if ($callback && is_callable($callback)) {
            return $callback($response);
        }

        return $response;
    }

    public static function close()
    {
        if (self::$ch) {
            curl_close(self::$ch);
        }
    }

    public static function getError()
    {
        if (self::$ch) {
            return curl_error(self::$ch);
        }

        return null;
    }

    public static function getInfo($opt)
    {
        if (self::$ch) {
            return curl_getinfo(self::$ch, $opt);
        }
        return null;
    }

    public static function getContent($url, $timeOut = 1, $header = [])
    {
        $header += [
            'Referer' => $url,
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:13.0) Gecko/20100101 Firefox/13.0.1'
        ];
        $headerStr = '';
        foreach ($header as $key => $val) {
            $headerStr .= "{$key}: {$url}\r\n";
        }
        $headerStr .= 'Header-Ext: ZPHP-Sync-Client';
        $strm = stream_context_create(array(
                'http' => array(
                    'timeout' => $timeOut,
                    'header' => $headerStr
                )
            )
        );
        return file_get_contents($url, 0, $strm);
    }

}