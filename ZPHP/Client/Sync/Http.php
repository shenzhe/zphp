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
     * @param $params = array(
     *                      'url'=> '目标网址',
     *                      'isPost'=>1,  //post方式
     *                      'dataStr'=> array(  //参数
     *                                      'k1'=>'v1',
     *                                      'k2'=>'v2'
     *                                  ),
     *                      'cookieFile' => '/tmp/xxx',  //cookie文件路径
     *                      'headers' => array( //自定义header请求头
     *                                      'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36'
     *                                  )
     *                  )
     *
     * @return mixed
     * @throws \Exception
     * @desc 发起curl请求并获取结果
     */
    public static function query($params)
    {
        self::init();

        curl_setopt(self::$ch, CURLOPT_HEADER, 0);
        curl_setopt(self::$ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt(self::$ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt(self::$ch, CURLOPT_TIMEOUT, 30);
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt(self::$ch, CURLOPT_REFERER, $params['url']);
        if (!empty($params['isPost'])) {
            curl_setopt(self::$ch, CURLOPT_POST, true);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, http_build_query($params['dataStr']));
            curl_setopt(self::$ch, CURLOPT_URL, $params['url']);
        } else {
            if (!empty($params['dataStr'])) {
                if (is_array($params['dataStr'])) {
                    $dataStr = http_build_query($params['dataStr']);
                } else {
                    $dataStr = $params['dataStr'];
                }
                if (strpos($params['url'], '?')) {
                    curl_setopt(self::$ch, CURLOPT_URL, $params['url'] . '&' . $dataStr);
                } else {
                    curl_setopt(self::$ch, CURLOPT_URL, $params['url'] . '?' . $dataStr);
                }
            } else {
                curl_setopt(self::$ch, CURLOPT_URL, $params['url']);
            }
        }
        if (!empty($params['cookieFile'])) {
            curl_setopt(self::$ch, CURLOPT_COOKIEFILE, $params['cookieFile']);
        }

        if(empty($params['headers']['User-Agent'])) {
            curl_setopt(self::$ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:13.0) Gecko/20100101 Firefox/13.0.1');
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = array_merge($headers, $params['headers']);
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