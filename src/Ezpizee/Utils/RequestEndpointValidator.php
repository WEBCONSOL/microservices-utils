<?php

namespace Ezpizee\Utils;

final class RequestEndpointValidator
{
    private static $endpoints = [];
    private static $uriParams = [];
    private static $contextProcessorNamespace = '';

    private function __construct()
    {
    }

    public static function validate(string $uri, $data = null)
    : void
    {
        self::loadEndpointsFromConfig($data);
        self::validateUri($uri);
    }

    protected static function loadEndpointsFromConfig($data)
    {
        if (empty(self::$endpoints)) {
            if (is_array($data)) {
                self::$endpoints = $data;
            }
            else if (file_exists($data)) {
                self::$endpoints = json_decode(file_get_contents($data), true);
            }
        }
    }

    protected static function validateUri(string $uri)
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
