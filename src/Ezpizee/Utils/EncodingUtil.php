<?php

namespace Ezpizee\Utils;

final class EncodingUtil
{
    private static $UUID_TYPE           = 'alphanumericUUID';
    private static $MD5_REGEX           = '/^[a-f0-9]{32}$/';
    private static $UUID_V4_REGEX1      = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX1_2    = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
    private static $UUID_V4_REGEX2      = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    private static $UUID_V4_REGEX2_2    = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9A-F]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private static $UUID_REGEX3         = '/[a-z]\d[a-z]\d[a-z]\d[a-z]\d[a-z]\d[a-z]\d$/i';
    private static $NUMERICS            = [0,1,2,3,4,5,6,7,8,9];
    private static $ALPHABETS           = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
    private static $NUM_ALPHABETS       = 25;
    private static $NUM_NUMERICS        = 9;

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
        return self::$UUID_TYPE === 'alphanumericUUID' ? self::alphanumericUUID() : self::v4uuid();
    }

    private static function v4uuid()
    : string
    {
        $data = random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function alphanumericUUID()
    : string
    {
        $n1 = rand(0, self::$NUM_ALPHABETS);
        $n2 = rand(0, self::$NUM_NUMERICS);
        $n3 = rand(0, self::$NUM_ALPHABETS);
        $n4 = rand(0, self::$NUM_NUMERICS);
        $n5 = rand(0, self::$NUM_ALPHABETS);
        $n6 = rand(0, self::$NUM_NUMERICS);
        $n7 = rand(0, self::$NUM_ALPHABETS);
        $n8 = rand(0, self::$NUM_NUMERICS);
        $n9 = rand(0, self::$NUM_ALPHABETS);
        $n10 = rand(0, self::$NUM_NUMERICS);
        $n11 = rand(0, self::$NUM_ALPHABETS);
        $n12 = rand(0, self::$NUM_NUMERICS);
        return self::$ALPHABETS[$n1].self::$NUMERICS[$n2].self::$ALPHABETS[$n3].
            self::$NUMERICS[$n4].self::$ALPHABETS[$n5].self::$NUMERICS[$n6].
            self::$ALPHABETS[$n7].self::$NUMERICS[$n8].self::$ALPHABETS[$n9].
            self::$NUMERICS[$n10].self::$ALPHABETS[$n11].self::$NUMERICS[$n12];
    }

    public static final function isValidUUID(string $id)
    : bool
    {
        return preg_match(self::$UUID_V4_REGEX1, $id) === 1 || preg_match(self::$UUID_V4_REGEX2, $id) === 1 ||
            preg_match(self::$UUID_V4_REGEX1_2, $id) === 1 || preg_match(self::$UUID_V4_REGEX2_2, $id) === 1 ||
            (strlen($id) === 12 && preg_match(self::$UUID_REGEX3, $id) === 1);
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
