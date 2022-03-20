<?php

namespace Ezpizee\Utils\Encryption;

use CryptoJS;
use InternalEncryption;

class Encryptor
{
    /** @var InternalEncryption $internalEncryption */
    private $internalEncryption = null;
    /** @var CryptoJS $cryptoPHPJS */
    private $cryptoPHPJS = null;

    public function __construct() {}

    public function internal(): InternalEncryption
    {
        if ($this->internalEncryption === null) {
            include __DIR__.DIRECTORY_SEPARATOR.'InternalEncryption.php';
            $this->internalEncryption = new InternalEncryption();
        }
        return $this->internalEncryption;
    }

    public function cryptoPHPJS(): CryptoJS
    {
        if ($this->cryptoPHPJS === null) {
            include __DIR__.DIRECTORY_SEPARATOR.'CryptoJS.php';
            $this->cryptoPHPJS = new CryptoJS();
        }
        return $this->cryptoPHPJS;
    }
}