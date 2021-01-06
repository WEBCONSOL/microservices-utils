<?php

namespace Ezpizee\Utils;

use InvalidArgumentException;

final class ArrayUtil
{
    const NOT_VALID_NAME_CHARS = '!"#%&\'()*+,./;<=>@[\\]^`{|}~';
    const NOT_VALID_SEGMENT_NAME_CHARS = "]";

    public static function search(string $key, array $context)
    {
        if ($key && sizeof($context)) {
            $keys = self::_splitVariableName($key);
            return self::_findVariable($keys, $context);
        }
        return null;
    }

    private static function _splitVariableName($variableName)
    : array
    {
        $bad_chars = preg_quote(self::NOT_VALID_NAME_CHARS, '/');
        $bad_seg_chars = preg_quote(self::NOT_VALID_SEGMENT_NAME_CHARS, '/');

        $name_pattern = "(?:[^"
            . $bad_chars
            . "\s]+)|(?:\[[^"
            . $bad_seg_chars
            . "]+\])";

        $check_pattern = "/^(("
            . $name_pattern
            . ")\.)*("
            . $name_pattern
            . ")\.?$/";

        $get_pattern = "/(?:" . $name_pattern . ")/";

        if (!preg_match($check_pattern, $variableName)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Variable name is invalid: "%s"',
                    $variableName
                )
            );
        }

        preg_match_all($get_pattern, $variableName, $matches);

        $chunks = array();
        foreach ($matches[0] as $chunk) {
            // Remove wrapper braces if needed
            if ($chunk[0] == '[') {
                $chunk = substr($chunk, 1, -1);
            }
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    private static function _findVariable($variable, $context)
    {
        $value = null;
        if (is_string($variable)) {
            $variable = explode('.', $variable);
        }
        foreach ($variable as $var) {
            if (is_array($context)) {
                if (isset($context[$var])) {
                    $value = $context[$var];
                    $context = $value;
                }
                else {
                    $value = null;
                }
            }
            else if ($context instanceof ListModel) {
                if ($context->has($var)) {
                    $value = $context->get($var);
                    $context = $value;
                }
                else {
                    $value = null;
                }
            }
            else {
                $value = null;
            }
        }
        return $value;
    }

    public static function jsonDecode(array &$arr)
    {
        if (!empty($arr)) {
            foreach ($arr as $i => $v) {
                if ($v) {
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
    }
}
