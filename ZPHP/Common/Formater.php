<?php
/**
 * author: shenzhe
 * Date: 13-6-17
 */

namespace ZPHP\Common;

class Formater
{
    public static function fatal($error, $trace=true)
    {
        $exceptionHash = array(
            'className' => 'fatal',
            'message' => $error['message'],
            'code' => -1,
            'file' => $error['file'],
            'line' =>$error['line'],
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

        return $exceptionHash;
    }

    public static function exception(\Exception $exception, $trace = true)
    {
        $exceptionHash = array(
            'className' => 'Exception',
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'trace' => array(),
            'server'=>$_SERVER,
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

                if (!empty($traceItem['args'])) {
                    foreach ($traceItem['args'] as $argsItem) {
                        $traceHash['args'][] = \var_export($argsItem, true);
                    }
                }

                $exceptionHash['trace'][] = $traceHash;
            }
        }

        return $exceptionHash;
    }

}