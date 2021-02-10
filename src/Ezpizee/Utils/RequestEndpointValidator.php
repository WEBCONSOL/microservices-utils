<?php

namespace Ezpizee\Utils;

final class RequestEndpointValidator
{
    private static $endpoints = [];
    private static $uriParams = [];
    private static $contextProcessorNamespace = '';
    private static $data = [];

    private function __construct() {}

    public static function validate(string $uri, $data = null)
    : void
    {
        $merge = false;
        if (!is_array($data) && !empty($data)) {
            $merge = !in_array($data, self::$data);
            if ($merge) {
                self::$data[] = $data;
            }
        }
        self::loadEndpointsFromConfig($data, $merge);
        self::validateUri($uri);
    }

    private static function loadEndpointsFromConfig($data, bool $merge)
    : void
    {
        if (empty(self::$endpoints) || $merge) {
            if (is_array($data)) {
                if ($merge) {
                    self::$endpoints = array_merge($data, self::$endpoints);
                }
                else {
                    self::$endpoints = $data;
                }
            }
            else if (file_exists($data)) {
                if ($merge) {
                    self::$endpoints = array_merge(
                        json_decode(file_get_contents($data), true),
                        self::$endpoints
                    );
                }
                else {
                    self::$endpoints = json_decode(file_get_contents($data), true);
                }
            }
        }
    }

    private static function validateUri(string $uri)
    : bool
    {
        foreach (self::$endpoints as $endpoint => $cp) {
            if (PathUtil::isUriMatch($endpoint, $uri)) {
                self::$contextProcessorNamespace = $cp . '\\ContextProcessor';
                self::$uriParams = PathUtil::getUriArgs($endpoint, $uri);
                return true;
            }
        }
        return false;
    }

    public static function getContextProcessorNamespace()
    : string
    {
        return self::$contextProcessorNamespace;
    }

    public static function getUriParam(string $key)
    : string
    {
        return isset(self::$uriParams[$key]) ? self::$uriParams[$key] : "";
    }

    public static function getEndpointsConfigData()
    : array
    {
        return self::$endpoints;
    }
}
