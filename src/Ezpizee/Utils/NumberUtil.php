<?php

namespace Ezpizee\Utils;

final class NumberUtil
{
    public static function isAllNumericValue($val)
    : bool
    {
        $val = is_array($val) ? $val : explode(",", $val);
        if (!sizeof($val)) {
            return false;
        }
        foreach ($val as $v) {
            if (!is_numeric($v)) {
                return false;
            }
        }
        return true;
    }
}
