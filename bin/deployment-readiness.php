<?php

declare(strict_types=1);

use AEFS\Core\Config;
use AEFS\Core\Database;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException(
        'De deploymentcontrole mag uitsluitend via de command line worden uitgevoerd.'
    );
}

require dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'vendor'
    . DIRECTORY_SEPARATOR
    . 'autoload.php';

$root = dirname(__DIR__);
$config = new Config($root . DIRECTORY_SEPARATOR . 'config');
$failures = 0;

$check = static function (
    bool $condition,
    string $success,
    string $failure
) use (&$failures): void {
    if ($condition) {
        fwrite(STDOUT, '[OK] ' . $success . PHP_EOL);

        return;
    }

    $failures++;
    fwrite(STDERR, '[FOUT] ' . $failure . PHP_EOL);
};

$check(
    version_compare(PHP_VERSION, '8.4.0', '>='),
    'PHP-versie is compatibel: ' . PHP_VERSION,
    'PHP 8.4 of hoger is vereist; actief: ' . PHP_VERSION
);

foreach (['pdo', 'pdo_mysql', 'openssl', 'zip'] as $extension) {
    $check(
        extension_loaded($extension),
        'PHP-extensie geladen: ' . $extension,
        'PHP-extensie ontbreekt: ' . $extension
    );
}

$environment = trim((string) $config->get('app.environment', ''));
$baseUrl = trim((string) $config->get('app.base_url', ''));
$appKey = trim((string) $config->get('app.app_key', ''));

$check(
    $environment === 'production',
    'Applicatieomgeving is production.',
    'config/local/app.php moet environment=production bevatten.'
);
$check(
    str_starts_with($baseUrl, 'https://'),
    'Publieke basis-URL gebruikt HTTPS.',
    'De productie-base_url moet een volledige HTTPS-URL zijn.'
);
$check(
    strlen($appKey) >= 32,
    'Een stabiele app-key is geconfigureerd.',
    'De app-key ontbreekt of is te kort.'
);

foreach (
    [
        'storage/cache',
        'storage/logs',
        'storage/mail-attachments',
        'storage/sessions',
        'storage/temp',
        'storage/uploads',
    ] as $directory
) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
    $check(
        is_dir($path) && is_writable($path),
        'Schrijfmap beschikbaar: ' . $directory,
        'Schrijfmap ontbreekt of is niet schrijfbaar: ' . $directory
    );
}

try {
    $database = new Database($config);
    $requiredTables = [
        'leden',
        'gebruikers',
        'groepen',
        'leden_groepen',
        'evenementen',
        'event_inschrijvingen',
        'event_inschrijving_dagen',
        'shift_types',
        'shifts',
        'shift_inschrijvingen',
        'mailings',
        'mailing_ontvangers',
        'instellingen',
    ];

    $statement = $database->query('SHOW TABLES');
    $available = array_map(
        static fn (array $row): string => (string) array_values($row)[0],
        $statement->fetchAll()
    );

    foreach ($requiredTables as $table) {
        $check(
            in_array($table, $available, true),
            'Databasetabel aanwezig: ' . $table,
            'Vereiste databasetabel ontbreekt: ' . $table
        );
    }
} catch (Throwable $throwable) {
    $failures++;
    fwrite(
        STDERR,
        '[FOUT] Databasecontrole mislukt: '
            . $throwable->getMessage()
            . PHP_EOL
    );
}

$mailEnabled = (bool) $config->get('mail.enabled', false);
$mailFrom = trim((string) $config->get('mail.from_address', ''));
$mailUrl = trim((string) $config->get('mail.application_url', ''));

$check(
    $mailEnabled,
    'Mailtransport is ingeschakeld.',
    'Mailtransport is niet ingeschakeld.'
);
$check(
    filter_var($mailFrom, FILTER_VALIDATE_EMAIL) !== false,
    'Geldig afzenderadres geconfigureerd.',
    'Geldig mail.from_address ontbreekt.'
);
$check(
    str_starts_with($mailUrl, 'https://'),
    'Mail-links gebruiken de publieke HTTPS-URL.',
    'mail.application_url moet de publieke HTTPS-URL bevatten.'
);

$mailWorkerEnabled = (bool) $config->get(
    'mail_worker.enabled',
    false
);
$mailWorkerToken = trim(
    (string) $config->get('mail_worker.token', '')
);

$check(
    $mailWorkerEnabled,
    'De beveiligde externe mailworker-ingang is ingeschakeld.',
    'De externe mailworker-ingang is niet ingeschakeld.'
);
$check(
    preg_match('/^[a-f0-9]{64}$/i', $mailWorkerToken) === 1,
    'De externe mailworker gebruikt een sterke 256-bit token.',
    'De externe mailworker-token ontbreekt of is ongeldig.'
);

fwrite(
    $failures === 0 ? STDOUT : STDERR,
    sprintf(
        '%sDeploymentcontrole afgerond: %d fout(en).%s',
        PHP_EOL,
        $failures,
        PHP_EOL
    )
);

exit($failures === 0 ? 0 : 1);
