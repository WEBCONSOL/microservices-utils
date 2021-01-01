<?php

namespace Ezpizee\Utils;

final class SKU
{
    protected static $PFX = 'SKU';

    private function __construct(){}

    public static function setPFX(string $pfx): void {self::$PFX = $pfx;}

    public static function gen(string $productTypeId): string {
        return self::$PFX.'-'.date('Y').'-'.$productTypeId.'-'.strtoupper(uniqid());
    }

    public static function isValid(string $sku): bool {
        if (strlen($sku) >= 26 && strlen($sku) <= 36) {
            $exp = explode('-', $sku);
            if (isset($exp[0]) && $exp[0] === self::$PFX) {
                if (isset($exp[1]) && strlen($exp[1]) === 4) {
                    if (isset($exp[2]) && is_numeric($exp[2]) && strlen($exp[2]) < 12) {
                        if (isset($exp[3]) && strlen($exp[3]) === 13) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getProductType(string $sku): int {
        $exp = explode('-', $sku);
        return (int)(isset($exp[2]) ? $exp[2] : '0');
    }
}