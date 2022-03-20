<?php

namespace Ezpizee\Utils;

class UUID
{
    private function __construct(){}
    public static function id(): string {return EncodingUtil::uuid();}
    public static function isValid(string $id): bool {return EncodingUtil::isValidUUID($id);}
}
