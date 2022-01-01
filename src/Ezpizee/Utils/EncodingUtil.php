<?php

namespace Ezpizee\Utils;

final class EncodingUtil
{
    private static $MD5_REGEX = '/^[a-f0-9]{32}$/';
    private static $UUID_V4_REGEX1 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX2 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public static function isBase64Encoded($val)
    : bool
    {
        return is_string($val) && base64_encode(base64_decode($val, true)) === $val;
    }

    public static function isValidJSON($str)
    : bool
    {
        return $str && is_string($str) && is_array(json_decode($str, true)) && (json_last_error() == JSON_ERROR_NONE);
    }

    public static function isValidMd5(string $md5)
    : bool
    {
        return preg_match(self::$MD5_REGEX, $md5) === 1;
    }

    public static final function uuid()
    : string
    {
        return strtolower(exec('uuidgen'));
    }

    public static final function isValidUUID(string $id)
    : bool
    {
        return preg_match(self::$UUID_V4_REGEX1, $id) === 1 || preg_match(self::$UUID_V4_REGEX2, $id) === 1;
    }

    public static function jsonDecode(array &$arr)
    : void
    {
        foreach ($arr as $i=>$v) {
            if (is_array($v)) {
                self::jsonDecode($arr[$i]);
            }
            else if (!is_numeric($v) && EncodingUtil::isValidJSON($v)) {
                $arr[$i] = json_decode($v, true);
                self::jsonDecode($arr[$i]);
            }
        }
    }
}
