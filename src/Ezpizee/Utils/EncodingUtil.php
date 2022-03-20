<?php

namespace Ezpizee\Utils;

final class EncodingUtil
{
    private static $MD5_REGEX           = '/^[a-f0-9]{32}$/';
    private static $UUID_V4_REGEX1      = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX1_2    = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX2      = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    private static $UUID_V4_REGEX2_2    = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9A-F]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private static $UUID_REGEX3         = '[a-zA-B]\d[a-zA-B]\d[a-zA-B]\d';
    private static $NUMERICS            = [0,1,2,3,4,5,6,7,8,9];
    private static $ALPHABETS           = ['a','A','b','B','c','C','d','D','e','E','f','F','g','G','h','H','i','I','j','J','k','K','l','L','m','M','n','N','o','O','p','P','q','Q','r','R','s','S','t','T','u','U','v','V','w','W','x','X','y','Y','z','Z'];

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

    public static function uuid(int $max=12)
    : string
    {
        $rands = [];
        $n1 = sizeof(self::$ALPHABETS) - 1;
        $n2 = sizeof(self::$NUMERICS) - 1;
        for($i=0; $i<$max; $i++) {
            $rands[] = $i%2 === 0 ? self::$ALPHABETS[rand(0, $n1)] : self::$NUMERICS[rand(0, $n2)];
        }
        return implode('', $rands);
    }

    public static function v4uuid()
    : string
    {
        $data = random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static final function isValidUUID(string $id)
    : bool
    {
        // v4uuid
        if (preg_match(self::$UUID_V4_REGEX1, $id) === 1 || preg_match(self::$UUID_V4_REGEX2, $id) === 1 ||
            preg_match(self::$UUID_V4_REGEX1_2, $id) === 1 || preg_match(self::$UUID_V4_REGEX2_2, $id) === 1) {
            return true;
        }
        $min = 6;
        $n = strlen($id);
        if ($n >= $min && $n % $min === 0) {
            $regex = '/'.str_repeat(self::$UUID_REGEX3, $n/$min).'$/i';
            return preg_match($regex, $id) === 1;
        }
        return false;
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
