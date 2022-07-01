<?php

namespace Ezpizee\Utils;

use FastRoute\Dispatcher;
use RuntimeException;

final class RequestEndpointValidator
{
    private static $endpoints = [];
    private static $uriParams = [];
    private static $contextProcessorNamespace = '';
    private static $data = [];
    private static $method = '';

    private function __construct() {}

    public static function validate(string $uri, $data = null, $method = 'GET', bool $stopOnError = true)
    : void
    {
        self::$method = $method;
        $merge = false;
        if (!is_array($data) && !empty($data)) {
            $merge = !in_array($data, self::$data);
            if ($merge) {
                self::$data[] = $data;
            }
        }
        self::loadEndpointsFromConfig($data, $merge);
        self::validateUri($uri, $stopOnError);
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

    private static function validateUri(string $uri, bool $stopOnError = true)
    : bool
    {
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            foreach (self::$endpoints as $endpoint => $cp) {
                if (is_string($cp)) {
                    if (self::$method === null) {
                        self::$method = 'GET';
                    }
                    $r->addRoute(self::$method, $endpoint, $cp);
                }
                else if (is_array($cp)) {
                    if (self::$method === null) {
                        if (isset($cp['GET'])) {
                            self::$method = 'GET';
                        }
                        else if (isset($cp['POST'])) {
                            self::$method = 'POST';
                        }
                        else if (isset($cp['PUT'])) {
                            self::$method = 'PUT';
                        }
                        else if (isset($cp['DELETE'])) {
                            self::$method = 'DELETE';
                        }
                    }
                    if (self::$method && isset($cp[self::$method])) {
                        $r->addRoute(self::$method, $endpoint, $cp[self::$method]);
                    }
                }
            }
        });

        // Fetch method and URI from somewhere
        $routeInfo = $dispatcher->dispatch(self::$method, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                if ($stopOnError) {
                    throw new RuntimeException(ResponseCodes::MESSAGE_ERROR_ITEM_NOT_FOUND, 404);
                }
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                if ($stopOnError) {
                    throw new RuntimeException(ResponseCodes::MESSAGE_ERROR_METHOD_NOT_ALLOWED,
                        ResponseCodes::CODE_METHOD_NOT_ALLOWED);
                }
                break;
            case Dispatcher::FOUND:
                self::$contextProcessorNamespace = $routeInfo[1] . '\\ContextProcessor';
                self::$uriParams = $routeInfo[2];
                return true;
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
        return isset(self::$uriParams[$key]) ? StringUtil::removeWhitespace(strip_tags(self::$uriParams[$key])) : "";
    }

    public static function getEndpointsConfigData()
    : array
    {
        return self::$endpoints;
    }
}
