<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 */

namespace ZPHP\Common;

use ZPHP\Core\Config as ZConfig;

class Formater
{
    public static function fatal($error, $trace = true, $name = 'fatal')
    {
        $exceptionHash = array(
            'className' => $name,
            'message' => '[type:' . $error['type'] . '] ' . $error['message'],
            'code' => ZConfig::getField('project', 'default_exception_code', -1),
            'file' => $error['file'],
            'line' => $error['line'],
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'trace' => array(),
        );

        if ($trace) {
            $traceItems = debug_backtrace();
            foreach ($traceItems as $traceItem) {
                $traceHash = array(
                    'file' => isset($traceItem['file']) ? $traceItem['file'] : 'null',
                    'line' => isset($traceItem['line']) ? $traceItem['line'] : 'null',
                    'function' => isset($traceItem['function']) ? $traceItem['function'] : 'null',
                    'args' => array(),
                );

                if (!empty($traceItem['class'])) {
                    $traceHash['class'] = $traceItem['class'];
                }

                if (!empty($traceItem['type'])) {
                    $traceHash['type'] = $traceItem['type'];
                }

                if (!empty($traceItem['args'])) {
                    foreach ($traceItem['args'] as $argsItem) {
                        $traceHash['args'][] = \var_export($argsItem, true);
                    }
                }

                $exceptionHash['trace'][] = $traceHash;
            }
        }
        $exceptionHash['_view_mode'] = 'Json';
        return $exceptionHash;
    }

    /**
     * @param $exception \Exception | \Error
     * @param bool $trace
     * @param bool $args
     * @return array
     * @throws \Exception
     */
    public static function exception($exception, $trace = true, $args = true)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();
        if (empty($code)) {
            $code = ZConfig::getField('project', 'default_exception_code', -1);
        } elseif (!is_numeric($code)) {
            $message .= "#code:[{$code}]";
            $code = ZConfig::getField('project', 'default_exception_code', -1);
        }

        $exceptionHash = array(
            'className' => get_class($exception),
            'message' => $message,
            'code' => $code,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'trace' => array(),
            'server' => ZConfig::get('server_mode') == 'Http' ? $_SERVER : '',
        );

        if ($trace) {
            $traceItems = $exception->getTrace();
            foreach ($traceItems as $traceItem) {
                $traceHash = array(
                    'file' => isset($traceItem['file']) ? $traceItem['file'] : 'null',
                    'line' => isset($traceItem['line']) ? $traceItem['line'] : 'null',
                    'function' => isset($traceItem['function']) ? $traceItem['function'] : 'null',
                    'args' => array(),
                );

                if (!empty($traceItem['class'])) {
                    $traceHash['class'] = $traceItem['class'];
                }

                if (!empty($traceItem['type'])) {
                    $traceHash['type'] = $traceItem['type'];
                }

                if ($args) {
                    if (!empty($traceItem['args'])) {
                        foreach ($traceItem['args'] as $argsItem) {
                            $traceHash['args'][] = \preg_replace('/[^(\x20-\x7F)]*/', '', \var_export($argsItem, true));
                        }
                    }
                }

                $exceptionHash['trace'][] = $traceHash;
            }
        }
        $exceptionHash['_view_mode'] = 'Json';
        return $exceptionHash;
    }

}