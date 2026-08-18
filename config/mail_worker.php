<?php

declare(strict_types=1);

$configuration = [
    'enabled' => false,
    'token' => '',
];

$localFile = __DIR__
    . DIRECTORY_SEPARATOR
    . 'local'
    . DIRECTORY_SEPARATOR
    . 'mail_worker.php';

if (is_file($localFile)) {
    $localConfiguration = require $localFile;

    if (!is_array($localConfiguration)) {
        throw new RuntimeException(
            'De lokale mailworkerconfiguratie moet een array teruggeven.'
        );
    }

    $configuration = array_replace(
        $configuration,
        $localConfiguration
    );
}

return $configuration;
