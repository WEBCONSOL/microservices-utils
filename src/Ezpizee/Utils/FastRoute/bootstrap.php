<?php

namespace Ezpizee\Utils\FastRoute;

require __DIR__ . '/functions.php';

spl_autoload_register(function ($class) {
    if (strpos($class, 'Ezpizee\\Utils\\FastRoute\\') === 0) {
        $name = substr($class, strlen('Ezpizee\\Utils\\FastRoute'));
        require __DIR__ . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});
