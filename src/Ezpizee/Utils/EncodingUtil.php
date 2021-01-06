<?php

namespace Ezpizee\Utils;

final class EncodingUtil
{
    private static $MD5_REGEX = '/^[a-f0-9]{32}$/';
    private static $UUID_V4_REGEX = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

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
        return strtoupper(exec('uuidgen'));
    }

    public static final function isValidUUID(string $id)
    : bool
    {
        return preg_match(self::$UUID_V4_REGEX, $id) === 1;
    }
}
