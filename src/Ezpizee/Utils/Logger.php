<?php

namespace Ezpizee\Utils;

final class Logger
{
    private static $DEBUG = false;

    private function __construct()
    {
    }

    public static function error($message, $message_type = null, $destination = null, $extra_headers = null)
    {
        if (!defined('ERROR_REPORTING_NO_ERROR')) {
            $calledIn = self::generateCallTrace(1);
            $message = self::formatMessage($message);
            self::log(($calledIn ? $calledIn . ' - ' : '') . '[error] ' . $message, $message_type, $destination, $extra_headers);
        }
    }

    public static function generateCallTrace(int $index = -1)
    : string
    {
        if (self::$DEBUG) {
            $trace = debug_backtrace();
            if ($index !== -1) {
                if (isset($trace[$index])) {
                    $trace = self::formatTrace($trace[$index]);
                }
                else {
                    foreach ($trace as $i => $arg) {
                        $trace[$i] = self::formatTrace($trace[$i]);
                    }
                }
            }
            return json_encode($trace);
        }
        return "";
    }

    private static function formatTrace(array $trace)
    {
        $o = [];
        if (isset($trace['file'])) {
            $o[] = $trace['file'] . (isset($trace['line']) ? '(' . $trace['line'] . ')' : '');
        }
        if (isset($trace['class'])) {
            $o[] = $trace['class'] . (isset($trace['function'])
                    ? '.' . $trace['function'] . (isset($trace['args'])
                        ? '(' . implode(', ', $trace['args']) . ')'
                        : '')
                    : ''
                );
        }
        return implode(' - ', $o);
    }

    private static function formatMessage($message)
    : string
    {
        if (!is_string($message)) {
            if (method_exists($message, 'getMessage')) {
                $newMessage = $message->getMessage();
                if (method_exists($message, 'getFile')) {
                    $newMessage = $newMessage . ' at ' . $message->getFile();
                    if (method_exists($message, 'getLine')) {
                        $newMessage = $newMessage . ' on line ' . $message->getLine();
                    }
                }
                $message = $newMessage;
            }
            else if (is_object($message) || is_array($message)) {
                $message = json_encode($message);
            }
            else {
                $message = '';
            }
        }
        return $message;
    }

    public static function log($message, $message_type = null, $destination = null, $extra_headers = null)
    {
        if ($message === '[error] Invalid HTTP status code') {
            $message = json_encode([
                'message'         => $message,
                'debug_backtrace' => debug_backtrace()
            ]);
        }
        error_log($message, $message_type, $destination, $extra_headers);
    }

    public static function debug($message, $message_type = null, $destination = null, $extra_headers = null)
    {
        if (!defined('ERROR_REPORTING_NO_DEBUG')) {
            $calledIn = self::generateCallTrace(1);
            $message = self::formatMessage($message);
            self::log(($calledIn ? $calledIn . ' - ' : '') . '[debug] ' . $message, $message_type, $destination, $extra_headers);
        }
    }

    public static function info($message, $message_type = null, $destination = null, $extra_headers = null)
    {
        if (!defined('ERROR_REPORTING_NO_INFO')) {
            $calledIn = self::generateCallTrace(1);
            $message = self::formatMessage($message);
            self::log(($calledIn ? $calledIn . ' - ' : '') . '[info] ' . $message, $message_type, $destination, $extra_headers);
        }
    }

    public static function warning($message, $message_type = null, $destination = null, $extra_headers = null)
    {
        if (!defined('ERROR_REPORTING_NO_WARNING')) {
            $calledIn = self::generateCallTrace(1);
            $message = self::formatMessage($message);
            self::log(($calledIn ? $calledIn . ' - ' : '') . '[warning] ' . $message, $message_type, $destination, $extra_headers);
        }
    }

    public static function testDisplay($val, bool $isJSON = false)
    : void
    {
        if (!$isJSON) {
            if (is_array($val) || is_object($val)) {
                print_r($val);
            }
            else if (is_string($val)) {
                echo $val;
            }
        }
        else {
            header('Content-type: application/json');
            if (is_array($val) || is_object($val)) {
                echo json_encode($val);
            }
            else if (is_string($val)) {
                echo json_encode([$val]);
            }
        }
        die();
    }
}
