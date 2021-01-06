<?php

namespace Ezpizee\Utils;

final class PHPAuth
{
    private static $username = "";
    private static $password = "";
    private static $basicAuthToken = "";
    private static $digestAuthToken = "";
    private static $bearerAuthToken = "";

    private function __construct()
    {
    }

    public static final function getUsername()
    : string
    {
        self::loadData();
        return self::$username;
    }

    private static function loadData()
    : void
    {
        if (empty(self::$username) && empty(self::$password)) {
            if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW']) {
                self::$username = $_SERVER['PHP_AUTH_USER'];
                self::$password = $_SERVER['PHP_AUTH_PW'];
            }
            else {
                $authorization = self::getBasicToken();
                if (EncodingUtil::isBase64Encoded($authorization)) {
                    $authorization = explode(':', base64_decode($authorization));
                    if (sizeof($authorization) === 2) {
                        self::$username = $authorization[0];
                        self::$password = $authorization[1];
                    }
                }
            }
        }
    }

    public static final function getBasicToken()
    : string
    {
        if (empty(self::$basicAuthToken)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authorization = $_SERVER['HTTP_AUTHORIZATION'];
                if (StringUtil::startsWith($authorization, 'Basic ')) {
                    self::$basicAuthToken = str_replace('Basic ', '', $authorization);
                }
            }
        }
        return self::$basicAuthToken;
    }

    public static final function getPassword()
    : string
    {
        self::loadData();
        return self::$password;
    }

    public static final function getClientId()
    : string
    {
        self::loadData();
        return self::$username;
    }

    public static final function getClientSecret()
    : string
    {
        self::loadData();
        return self::$password;
    }

    public static final function getDigestToken()
    : string
    {
        if (empty(self::$digestAuthToken)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authorization = $_SERVER['HTTP_AUTHORIZATION'];
                if (StringUtil::startsWith($authorization, 'Digest ')) {
                    self::$digestAuthToken = str_replace('Digest ', '', $authorization);
                }
            }
        }
        return self::$digestAuthToken;
    }

    public static final function getBearerToken()
    : string
    {
        if (empty(self::$bearerAuthToken)) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authorization = $_SERVER['HTTP_AUTHORIZATION'];
                if (StringUtil::startsWith($authorization, 'Bearer ')) {
                    self::$bearerAuthToken = str_replace('Bearer ', '', $authorization);
                }
            }
        }
        return self::$bearerAuthToken;
    }
}
