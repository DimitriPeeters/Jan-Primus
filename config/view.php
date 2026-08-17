<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);
$appConfiguration = require __DIR__
    . DIRECTORY_SEPARATOR
    . 'app.php';

$configuredBaseUrl = trim(
    (string) ($appConfiguration['base_url'] ?? '')
);
$environment = trim(
    (string) ($appConfiguration['environment'] ?? 'production')
);

return [
    'paths' => [
        $rootPath
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Views',
    ],

    'namespaces' => [
        'core' => $rootPath
            . DIRECTORY_SEPARATOR
            . 'src'
            . DIRECTORY_SEPARATOR
            . 'Core'
            . DIRECTORY_SEPARATOR
            . 'View'
            . DIRECTORY_SEPARATOR
            . 'Views',
    ],

    'base_url' => $configuredBaseUrl !== ''
        ? rtrim($configuredBaseUrl, '/')
        : '/aefs-v2/public',

    'asset_path' => 'assets',

    'debug' => $environment !== 'production',
];
