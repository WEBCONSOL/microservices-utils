<?php

namespace Ezpizee\Utils;

class Constants
{
    const ACCESS_TOKEN_TLS_VALUE = 1 * 60 * 60 * 1000;
    const HEADER_ACCESS_TOKEN = "Access-Token";
    const HEADER_APP_NAME = "App-Name";
    const HEADER_STORE_ID = "Store-ID";
    const HEADER_AUTHORIZATION = "Authorization";
    const HEADER_SESSION_ID = "Session-Id";
    const HEADER_CUSTOMER_USER_ID = "Customer-UserId";
    const HEADER_USER_ID = "User-Id";
    const HEADER_EMPLOYEE_USER_ID = "Employee-UserId";
    const HEADER_LANGUAGE_TAG = "Ez-X-Lang";
    const HEADER_USER_AGENT = "User-Agent";
    const HEADER_X_USER_AGENT = "x-user-agent";
    const HEADER_APP_VERSION = "App-Version";
    const HEADER_APP_PLATFORM = "App-Platform";
    const HEADER_OS_PLATFORM_VERSION = "OS-Platform-Version";
    const HEADER_AUTHORIZATION_BEARER_TOKEN = "AuthorizationBearerToken";
    const HEADER_AUTHORIZATION_BEARER_PFX = "Bearer ";
    const HEADER_AUTHORIZATION_BASIC_PFX = "Basic ";
    const EZPZ_FAKE_PHONE_PFX = "ezpznotphone";
    const EZPZ_FAKE_EMAIL_PFX = "ezpznotemail";
}
