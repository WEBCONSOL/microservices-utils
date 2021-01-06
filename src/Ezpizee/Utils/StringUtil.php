<?php

namespace Ezpizee\Utils;

use Exception;
use stdClass;

final class StringUtil
{
    public static function endsWith($haystack, $needle)
    : bool
    {
        return substr($haystack, -strlen(strlen($needle))) === $needle;
    }

    public static function humanTiming($time)
    {
        $time = time() - (int)$time; // to get the time since that moment
        $time = ($time < 1) ? 1 : $time;
        $tokens = array(
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
            1        => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
        }
        return $time;
    }

    public static function calPercentage($value, $ratio, $decimalPlaces = 2)
    {
        if ($ratio < 1) {
            return number_format(($ratio * $value), $decimalPlaces);
        }
        else if ($ratio > 1) {
            return number_format((($ratio * $value) / 100), $decimalPlaces);
        }
        else {
            return $value;
        }
    }

    public static function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        $base = 1024;
        $i = (int)(floor(log($size, $base))) . '';
        return @round($size / pow($base, $i), 2) . ' ' . (isset($unit[$i]) ? $unit[$i] : $unit[0]);
    }

    public static function convertToBytes($from)
    {
        if (is_numeric($from)) {
            return $from;
        }
        $alphabet = preg_replace('/[0-9 ]+/', '', $from);
        $number = str_replace($alphabet, '', $from);
        $base = 1024;

        switch (strtoupper($alphabet)) {
            case "KB":
            case "K":
                return $number * $base;
            case "MB":
            case "M":
                return $number * pow($base, 2);
            case "GB":
            case "G":
                return $number * pow($base, 3);
            case "TB":
            case "T":
                return $number * pow($base, 4);
            case "PB":
            case "P":
                return $number * pow($base, 5);
            default:
                return $from;
        }
    }

    public static function getStringBetween($haystack, $startStr, $endStr)
    : string
    {
        $n1 = strpos($haystack, $startStr);
        $n2 = strpos($haystack, $endStr);
        if ($n1 !== false && $n2 !== false && $n2 > $n1) {
            $n1 = $n1 + strlen($startStr);
            $n = $n2 - $n1;
            if ($n >= 0) {
                return substr($haystack, $n1, $n);
            }
        }
        return "";
    }

    public static function str2regex($str)
    : string
    {
        return $str && is_string($str) ? "/" . preg_quote($str, "/") . "/" : "";
    }

    public static function alphaNumericOnly($string)
    : string
    {
        return preg_replace("/[^A-Za-z0-9 ]/", '', $string);
    }

    public static function getJSONContent($file)
    : stdClass
    {
        return json_decode(file_get_contents($file));
    }

    public static function isEmail($str)
    : bool
    {
        return filter_var($str, FILTER_VALIDATE_EMAIL) ? true : false;
    }

    public static function isUrl($str)
    : bool
    {
        return filter_var($str, FILTER_VALIDATE_URL) ? true : false;
    }

    public static function isInt($str)
    : bool
    {
        return filter_var($str, FILTER_VALIDATE_INT) ? true : false;
    }

    public static function isIP($str)
    : bool
    {
        return filter_var($str, FILTER_VALIDATE_IP) ? true : false;
    }

    public static function isNotHttps($uri)
    : bool
    {
        return !self::isHttps($uri);
    }

    public static function isHttps($uri)
    : bool
    {
        return self::startsWith($uri, "https://");
    }

    public static function startsWith($haystack, $needle)
    : bool
    {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }

    public static function toRegex($str)
    : string
    {
        if (self::isRegExp($str)) {
            return '/' . addcslashes($str, "/\n\t\r") . '/';
        }
        return $str;
    }

    public static function isRegExp($str)
    : bool
    {
        try {
            if (@preg_match($str, '') !== false) {
                return true;
            }
            $newStr = '/' . addcslashes($str, "/\n\t\r") . '/';
            if (@preg_match($newStr, '') !== false) {
                return true;
            }
            if (strpos($newStr, '(') !== false && strpos($newStr, ')') !== false && strpos($newStr, '.') !== false &&
                strpos($newStr, '*') !== false) {
                return true;
            }
            if (strpos($newStr, '[') !== false && strpos($newStr, ']') !== false) {
                return true;
            }
        }
        catch (Exception $e) {
            Logger::error($e->getMessage());
        }
        return false;
    }

    public static function parseString($str)
    : array
    {
        $queries = array();
        $base64Decode = base64_encode(base64_decode($str, true)) === $str ? base64_decode($str) : $str;
        if ($base64Decode) {
            parse_str($base64Decode, $queries);
        }
        return $queries;
    }

    public static function removeHtmlComments(string $str)
    : string
    {
        return preg_replace('/<!--(.[^>]*?)-->/', '', $str);
    }

    public static function removeDoubleSlashes(string $str)
    : string
    {
        return preg_replace('/\/+/', '/', $str);
    }

    public static function hashedPath(string $path)
    : string
    {
        $addSlash = self::startsWith($path, '/');
        return md5(($addSlash ? '/' : '') . trim($path, '/'));
    }

    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }

    public static function cleanCommaSeparatedIds(string $ids)
    : array
    {
        $ids = explode(',', $ids);
        $newIds = [];
        foreach ($ids as $i => $id) {
            if ($id) {
                $newIds[] = (int)strip_tags($id);
            }
        }
        return $newIds;
    }

    public static function cleanCommaSeparatedString(string $ids)
    : array
    {
        $ids = explode(',', $ids);
        $newIds = [];
        foreach ($ids as $i => $id) {
            if ($id) {
                $newIds[] = strip_tags($id);
            }
        }
        return $newIds;
    }
}
