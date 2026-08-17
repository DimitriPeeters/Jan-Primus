<?php

declare(strict_types=1);

$configuration = [
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'database' => $_ENV['DB_DATABASE'] ?? 'aefs_v2',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];

$localFile = __DIR__
    . DIRECTORY_SEPARATOR
    . 'local'
    . DIRECTORY_SEPARATOR
    . 'database.php';

if (is_file($localFile)) {
    $localConfiguration = require $localFile;

    if (!is_array($localConfiguration)) {
        throw new RuntimeException(
            'De lokale databaseconfiguratie moet een array teruggeven.'
        );
    }

    $configuration = array_replace_recursive(
        $configuration,
        $localConfiguration
    );
}

return $configuration;
