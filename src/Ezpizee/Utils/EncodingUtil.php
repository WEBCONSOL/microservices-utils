<?php

namespace Ezpizee\Utils;

final class EncodingUtil
{
    private static $MD5_REGEX = '/^[a-f0-9]{32}$/';
    private static $UUID_V4_REGEX1 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX1_2 = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX2 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    private static $UUID_V4_REGEX2_2 = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9A-F]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

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
        $uuid = exec('uuidgen');
        if (empty($uuid)) {
            $data = random_bytes(16);
            assert(strlen($data) == 16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        return strtolower($uuid);
    }

    public static final function isValidUUID(string $id)
    : bool
    {
        return preg_match(self::$UUID_V4_REGEX1, $id) === 1 || preg_match(self::$UUID_V4_REGEX2, $id) === 1 ||
            preg_match(self::$UUID_V4_REGEX1_2, $id) === 1 || preg_match(self::$UUID_V4_REGEX2_2, $id) === 1;
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
