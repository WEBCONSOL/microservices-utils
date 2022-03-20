<?php

namespace Ezpizee\Utils;

class UUID
{
    private function __construct(){}
    public static function id(int $max=12): string {return EncodingUtil::uuid($max);}
    public static function isValid(string $id): bool {return EncodingUtil::isValidUUID($id);}
}
