<?php

use Defuse\Crypto\Crypto as DefuseCrypto;
use Ezpizee\Utils\ResponseCodes;
use RuntimeException;

final class InternalEncryption
{
    /**
     * @param string $plaintext
     * @param string $passPhrase
     * @return string
     */
    public function encrypt(string $plaintext, string $passPhrase=''): string
    {
        if (empty($passPhrase) && defined('INTERNAL_DATA_ENCRYPTION_PHRASE')) {
            $passPhrase = INTERNAL_DATA_ENCRYPTION_PHRASE;
        }
        if (empty($passPhrase)) {
            throw new RuntimeException(ResponseCodes::CODE_ERROR_INVALID_DATA, 'MISSING_ENCRYPTION_PASSPHRASE');
        }
        return DefuseCrypto::encryptWithPassword($plaintext, $passPhrase);
    }

    /**
     * @param string $cipherText
     * @param string $passPhrase
     * @return string
     */
    public function decrypt(string $cipherText, string $passPhrase=''): string
    {
        if (empty($passPhrase) && defined('INTERNAL_DATA_ENCRYPTION_PHRASE')) {
            $passPhrase = INTERNAL_DATA_ENCRYPTION_PHRASE;
        }
        if (empty($passPhrase)) {
            throw new RuntimeException(ResponseCodes::CODE_ERROR_INVALID_DATA, 'MISSING_ENCRYPTION_PASSPHRASE');
        }
        return DefuseCrypto::decryptWithPassword($cipherText, $passPhrase);
    }
}