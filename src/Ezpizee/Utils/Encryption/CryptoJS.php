<?php

use Ezpizee\Utils\EncodingUtil;
use Ezpizee\Utils\ResponseCodes;

final class CryptoJS
{
    public function encrypt(string $plaintext, string $passPhrase='', bool $toBase64=false): string
    {
        if (empty($passPhrase) && defined('DATA_ENCRYPTION_PHRASE')) {
            $passPhrase = DATA_ENCRYPTION_PHRASE;
        }
        if (empty($passPhrase)) {
            throw new RuntimeException(ResponseCodes::CODE_ERROR_INVALID_DATA, 'MISSING_ENCRYPTION_PASSPHRASE');
        }
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx.$passPhrase.$salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);
        $encrypted_data = openssl_encrypt($plaintext, 'aes-256-cbc', $key, true, $iv);
        $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
        return $toBase64 ? base64_encode(json_encode($data)) : json_encode($data);
    }

    public function decrypt(string $cipherText, string $passPhrase=''): string
    {
        if (empty($passPhrase) && defined('DATA_ENCRYPTION_PHRASE')) {
            $passPhrase = DATA_ENCRYPTION_PHRASE;
        }
        if (empty($passPhrase)) {
            throw new RuntimeException(ResponseCodes::CODE_ERROR_INVALID_DATA, 'MISSING_ENCRYPTION_PASSPHRASE');
        }
        $data = null;
        if (EncodingUtil::isBase64Encoded($cipherText)) {
            $cipherText = base64_decode($cipherText);
        }
        $jsonData = json_decode($cipherText, true);
        if ($jsonData && isset($jsonData["ct"]) && isset($jsonData["iv"]) && isset($jsonData["s"]) &&
            !empty($jsonData["ct"]) && !empty($jsonData["iv"]) && !empty($jsonData["s"])) {
            $salt = hex2bin($jsonData["s"]);
            $ct = base64_decode($jsonData["ct"]);
            $iv  = hex2bin($jsonData["iv"]);
            $concatedPassPhrase = $passPhrase.$salt;
            $md5 = array();
            $md5[0] = md5($concatedPassPhrase, true);
            $result = $md5[0];
            for ($i = 1; $i < 3; $i++) {
                $md5[$i] = md5($md5[$i - 1].$concatedPassPhrase, true);
                $result .= $md5[$i];
            }
            $key = substr($result, 0, 32);
            $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
        }
        return empty($data) ? '' : $data;
    }
}