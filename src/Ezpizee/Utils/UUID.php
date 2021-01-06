<?php

namespace Ezpizee\Utils;

class UUID
{
    private function __construct()
    {
    }

    public static final function id()
    : string
    {
        return EncodingUtil::uuid();
    }

    public static final function isValid(string $id)
    : bool
    {
        return EncodingUtil::isValidUUID($id);
    }
}
