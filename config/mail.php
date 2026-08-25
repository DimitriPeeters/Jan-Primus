<?php

declare(strict_types=1);

$configuration = [
    'enabled' => false,
    'host' => 'mailout.one.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'medewerkers@jan-primus.be',
    'password' => '',
    'from_address' => 'medewerkers@jan-primus.be',
    'from_name' => 'vzw Jan Primus',
    'reply_to_address' => 'info@jan-primus.be',
    'reply_to_name' => 'vzw Jan Primus',
    'application_url' => '',
    'timeout_seconds' => 20,
    'batch_size' => 25,
    'max_attempts' => 5,
    'attachment_max_bytes' => 10 * 1024 * 1024,
    'recipient_allowlist' => [],
];

$localFile = __DIR__
    . DIRECTORY_SEPARATOR
    . 'local'
    . DIRECTORY_SEPARATOR
    . 'mail.php';

if (is_file($localFile)) {
    $localConfiguration = require $localFile;

    if (!is_array($localConfiguration)) {
        throw new RuntimeException(
            'De lokale mailconfiguratie moet een array teruggeven.'
        );
    }

    $configuration = array_replace(
        $configuration,
        $localConfiguration
    );
}

$localRecipientsFile = __DIR__
    . DIRECTORY_SEPARATOR
    . 'local'
    . DIRECTORY_SEPARATOR
    . 'mail-recipients.php';

if (is_file($localRecipientsFile)) {
    $configuration['recipient_allowlist'] = require $localRecipientsFile;
}

return $configuration;
