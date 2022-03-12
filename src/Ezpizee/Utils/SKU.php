<?php

namespace Ezpizee\Utils;

class SKU
{
    protected static $PFX = 'SKU';

    public final static function getPFX(): string {return self::$PFX;}

    public final static function gen(string $productTypeId)
    : string
    {
        return self::getPFX() . '-' . date('Y') . '-' . $productTypeId . '-' . strtolower(uniqid());
    }

    public final static function isValid(string $sku)
    : bool
    {
        $exp = explode('-', $sku);
        if (sizeof($exp) >= 4 && $exp[0] === self::getPFX() && is_numeric($exp[1]) && strlen($exp[1]) === 4) {
            $lastBit = $exp[sizeof($exp)-1];
            $productTypeId = str_replace([$exp[0].'-'.$exp[1].'-', '-'.$lastBit], '', $sku);
            return EncodingUtil::isValidUUID($productTypeId) && strlen($lastBit) === 13;
        }
        return false;
    }

    public final static function parse(string $sku)
    : array
    {
        $parsed = [
            'pfx' => self::getPFX(),
            'year' => 0,
            'productTypeId' => '',
            'uniqid' => ''
        ];
        if (self::isValid($sku)) {
            $exp = explode('-', $sku);
            $lastBit = $exp[sizeof($exp)-1];
            $parsed['pfx'] = $exp[0];
            $parsed['year'] = (int)$exp[1];
            $parsed['productTypeId'] = str_replace([$exp[0].'-'.$exp[1].'-', '-'.$lastBit], '', $sku);
            $parsed['uniqid'] = $lastBit;
        }
        return $parsed;
    }

    public final static function getYear(string $sku)
    : int
    {
        $parsed = self::parse($sku);
        return (int)$parsed['year'];
    }

    public final static function getProductType(string $sku)
    : string
    {
        $parsed = self::parse($sku);
        return $parsed['productTypeId'];
    }

    public final static function getUniqid(string $sku)
    : string
    {
        $parsed = self::parse($sku);
        return $parsed['uniqid'];
    }
}
