<?php

namespace Ezpizee\Utils;

use RuntimeException;

class Password
{
    private function __construct(){}

    public static final function encrypt(string $pwd): string
    {
        if ($pwd) {
            return password_hash($pwd, PASSWORD_BCRYPT);
        }
        else {
            throw new RuntimeException('Password cannot be blank.');
        }
    }

    public static final function verify(string $pwd, string $hashedPwd): bool
    {
        if ($pwd && $hashedPwd) {
            return password_verify($pwd, $hashedPwd);
        }
        else {
            throw new RuntimeException('Password and hashed password cannot be blank.');
        }
    }

    public static function random(): string
    {
        $passwod = EncodingUtil::uuid(12);
        $rand = rand(0, sizeof(Constants::ALPHABETS)-1);
        $c1 = strtoupper(Constants::ALPHABETS[$rand]);
        $rand = rand(0, sizeof(Constants::ALPHABETS)-1);
        $c2 = strtolower(Constants::ALPHABETS[$rand]);
        $rand1 = rand(0, strlen($passwod)-1);
        $rand2 = rand(0, strlen($passwod)-1);
        $passwod[$rand1] = $c1;
        $passwod[$rand2] = $c2;
        return $passwod;
    }

    /**
     * Must be:
     * - greater than 7 characters & less than 25 characters
     * - contains at least 1 lower case character
     * - contains at least 1 upper case character
     * - contains at least 1 digit
     * - contains NO white space character
     * - contains at least 1 special character character
     *
     * @param $password
     *
     * @return bool
     */
    public static function isValid($password): bool
    {
        // check upper case existence
        $uppercase = preg_match("/[A-Z]/", $password);
        // check lower case existence
        $lowercase = preg_match("/[a-z]/", $password);
        // check number existence
        $number = preg_match("/[0-9]/", $password);
        // check if it contains space
        $space = preg_match("/\s/", $password);
        // check if it contains at least 1 special character
        //$space    = preg_match("/\W/", $password);
        if (!$uppercase || !$lowercase || !$number || $space || strlen($password) < 8 || strlen($password) > 24) {
            return false;
        }
        return true;
    }
}
