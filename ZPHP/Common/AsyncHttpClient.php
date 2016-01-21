<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 14-5-26
 * Time: 下午12:18
 */

namespace ZPHP\Common;


class AsyncHttpClient
{
    private static $buffer = [];
    public static function request(callable $callback, $url, $method='GET', array $headers=[], array $params=[])
    {
        $parsed_url = parse_url($url);
        \swoole_async_dns_lookup($parsed_url['host'], function($host, $ip) use ($parsed_url, $callback, $url, $method, $headers, $params) {
            $port = isset($parsed_url['port']) ? $parsed_url['port'] : 'https' == $parsed_url['scheme'] ? 443 : 80;
            $client = new \swoole_client(SWOOLE_SOCK_TCP,  SWOOLE_SOCK_ASYNC);
            $method = strtoupper($method);
            $client->on("connect", function($cli) use($url, $method, $parsed_url, $headers, $params) {
                \ZPHP\Common\AsyncHttpClient::clear($cli);
                $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
                if(!empty($params)) {
                    $query = http_build_query($params);
                    if ('GET' == $method) {
                        $path .= "?" . $query;
                    }
                }
                $sendData = $method." {$path} HTTP/1.1\r\n";
                $headers = array(
                        'Host'=>$parsed_url['host'],
                        'Connection'=>'keep-alive',
                        'Pragma'=>'no-cache',
                        'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'User-Agent'=>'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36',
                        'Referer'=>$url,
                        'Accept-Encoding'=>'gzip, deflate, sdch',
                        'Accept-Language'=>'zh-CN,zh;q=0.8',
                    ) + $headers;


                foreach($headers as $key=>$val) {
                    $sendData.="{$key}: {$val}\r\n";
                }

                if('POST' === $method) {
                    $sendData.="Content-Length: ".strlen($query)."\r\n";
                    $sendData.="\r\n".$query;
                } else {
                    $sendData.="\r\n";
                }
                $cli->send($sendData);
            });
            $client->on("receive", function($cli, $data) use ($callback) {
                $ret = self::parseBody($cli, $data, $callback);
                if(is_array($ret)) {
                    call_user_func_array($callback, array($cli, $ret));
                }
            });
            $client->on("error", function($cli){
                \ZPHP\Common\AsyncHttpClient::clear($cli);
            });
            $client->on("close", function($cli){
                \ZPHP\Common\AsyncHttpClient::clear($cli);
            });
            $client->connect($ip, $port);
        });

    }

    public static function clear($cli)
    {
        self::$buffer[$cli->sock] = null;
    }

    public static function parseBody($cli, $content, $callback)
    {
        if(empty(self::$buffer[$cli->sock])) {
            list($header, $body) = explode("\r\n\r\n", $content, 2);
            $headers = explode("\r\n", $header);
            $status = array_shift($headers);
            $statusArr = explode(" ", $status, 3);
            $headerArr = [];
            foreach($headers as $item) {
                $tmp = explode(':', $item, 2);
                $headerArr[$tmp[0]] = trim($tmp[1]);
            }
            self::$buffer[$cli->sock]['result']['status'] = $statusArr;
            self::$buffer[$cli->sock]['result']['header'] = $headerArr;
            if(in_array($statusArr[1], [301, 302])) {
                return \ZPHP\Common\AsyncHttpClient::request($callback, $headerArr['Location']);
            }
            self::outPut($cli, $body);
        } else {
            self::outPut($cli, $content);
        }

        if(!empty(self::$buffer[$cli->sock]['err'])) {
            self::clear($cli);
            return false;
        }

        if(!empty(self::$buffer[$cli->sock]['finish'])) {
            if(!empty(self::$buffer[$cli->sock]['result']['header']['Content-Encoding'])) {
                switch (self::$buffer[$cli->sock]['result']['header']['Content-Encoding']) {
                    case 'gzip':
                        self::$buffer[$cli->sock]['result']['body'] =  gzdecode(self::$buffer[$cli->sock]['buffer']);
                        break;
                    case 'deflate':
                        self::$buffer[$cli->sock]['result']['body'] =  gzinflate(self::$buffer[$cli->sock]['buffer']);
                        break;
                    case 'compress':
                        self::$buffer[$cli->sock]['result']['body'] =  gzinflate(substr(self::$buffer[$cli->sock]['buffer'], 2, -4));
                        break;
                    default:
                        self::$buffer[$cli->sock]['result']['body'] =  self::$buffer[$cli->sock]['buffer'];
                        break;
                }
            } else {
                self::$buffer[$cli->sock]['result']['body'] =  self::$buffer[$cli->sock]['buffer'];
            }

            $result =  self::$buffer[$cli->sock]['result'];
            self::clear($cli);
            return $result;
        }
    }

    public static function outPut($cli, $content)
    {
        if(isset(self::$buffer[$cli->sock]['result']['header']['Transfer-Encoding'])
            && 'chunked' == self::$buffer[$cli->sock]['result']['header']['Transfer-Encoding']
        ) {
            if(empty(self::$buffer[$cli->sock]['chunkLen'])) {
                $len = strstr($content, "\r\n", true);
                $length = hexdec($len);
                if ($length == 0) {
                    self::$buffer[$cli->sock]['finish'] = 1;
                    self::$buffer[$cli->sock]['buffer'] = substr(self::$buffer[$cli->sock]['buffer'], 0, strlen(self::$buffer[$cli->sock]['buffer']) - strlen($content));
                    return;
                }
                self::$buffer[$cli->sock]['chunkLen'] = $length;
                self::$buffer[$cli->sock]['buffer'].= substr($content, strlen($len)+2);
            } else {
                self::$buffer[$cli->sock]['buffer'].= $content;
            }
            if(strlen(self::$buffer[$cli->sock]['buffer']) >= self::$buffer[$cli->sock]['chunkLen']) {
                $len = self::$buffer[$cli->sock]['chunkLen'];
                self::$buffer[$cli->sock]['chunkLen'] = 0;
                self::outPut($cli, substr(self::$buffer[$cli->sock]['buffer'], $len));
            }
        } else {
            self::$buffer[$cli->sock]['buffer'] .= $content;
            if(strlen(self::$buffer[$cli->sock]['buffer']) >= self::$buffer[$cli->sock]['result']['header']['Content-Length']) {
                self::$buffer[$cli->sock]['finish'] = 1;
                self::$buffer[$cli->sock]['buffer'] = substr(self::$buffer[$cli->sock]['buffer'], 0, self::$buffer[$cli->sock]['result']['header']['Content-Length']);
                return;
            }
        }
    }
} 