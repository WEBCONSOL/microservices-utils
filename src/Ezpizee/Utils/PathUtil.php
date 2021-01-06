<?php

namespace Ezpizee\Utils;

final class PathUtil
{
    public static function toSlug($s, $delimiter = '-', $keepCase = false)
    : string
    {
        $s = $keepCase ? $s : strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9-]/', $delimiter, $s);
        if ($delimiter) {
            $s = preg_replace('/' . $delimiter . '+/', $delimiter, $s);
            return trim($s, $delimiter);
        }
        return $s;
    }

    public static function formatUri(string $uri, array $params)
    : string
    {
        $parts = explode("?", $uri);
        if (sizeof($parts) > 1) {
            $parts2 = explode('&', $parts[1]);
            foreach ($parts2 as $key => $part) {
                $parts3 = explode('=', $part);
                if (sizeof($parts3) === 2 && $parts[1]) {
                    $parts3[1] = str_replace(array('{', '}', ':'), '', $parts3[1]);
                    $parts3[1] = isset($params[$parts3[1]]) ? $params[$parts3[1]] : $parts3[1];
                }
                $parts2[$key] = implode('=', $parts3);
            }
            $parts[1] = implode('&', $parts2);
            $uri = implode("?", $parts);
        }
        else {
            $parts = explode("/", $uri);
            foreach ($parts as $key => $part) {
                if (strpos($part, "{") !== false && strpos($part, "}") !== false) {
                    $part = str_replace(array("{", "}"), "", $part);
                    if (isset($params[$part])) {
                        $parts[$key] = $params[$part];
                    }
                }
            }
            $uri = implode("/", $parts);
        }

        return $uri;
    }

    public static function isUriMatch(string $uriPattern, string $uri)
    : bool
    {
        $found = 0;
        $parts1 = explode('/', $uriPattern);
        $parts2 = explode('/', $uri);
        if (sizeof($parts1) === sizeof($parts2)) {
            foreach ($parts1 as $key => $str) {
                if ($str === $parts2[$key]) {
                    $found++;
                }
                else if (strlen($str) >= 2 && $str[0] === '{' && $str[strlen($str) - 1] === '}') {
                    $found++;
                }
            }
        }
        return $found === sizeof($parts1);
    }

    public static function getUriArgs(string $uriPattern, string $uri)
    : array
    {
        $args = array();
        $found = 0;
        $parts1 = explode('/', $uriPattern);
        $parts2 = explode('/', $uri);
        if (sizeof($parts1) === sizeof($parts2)) {
            foreach ($parts1 as $key => $str) {
                if ($str === $parts2[$key]) {
                    $found++;
                }
                else if ((strlen($str) >= 2 && $str[0] === '{' && $str[strlen($str) - 1] === '}') || (strlen($str) >= 2 && $str[0] === ':')) {
                    $found++;
                    $args[str_replace(array('{', '}', ':'), '', $str)] = $parts2[$key];
                }
            }
        }
        if ($found === sizeof($parts1)) {
            return $args;
        }
        return array();
    }
}
