<?php

declare(strict_types=1);

use AEFS\Core\Application;
use App\Services\MailQueueProcessor;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException(
        'De mailworker mag uitsluitend via de command line worden uitgevoerd.'
    );
}

require dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'vendor'
    . DIRECTORY_SEPARATOR
    . 'autoload.php';

$sessionPath = dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'storage'
    . DIRECTORY_SEPARATOR
    . 'sessions';

if (is_dir($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

/** @var Application $application */
$application = require dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'bootstrap'
    . DIRECTORY_SEPARATOR
    . 'app.php';

$limit = null;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--limit=')) {
        $limit = max(
            1,
            (int) substr($argument, strlen('--limit='))
        );
    }
}

/** @var MailQueueProcessor $processor */
$processor = $application->make(MailQueueProcessor::class);
$result = $processor->process($limit);

fwrite(
    STDOUT,
    sprintf(
        'Mailqueue verwerkt: %d behandeld, %d verzonden, %d mislukt.%s',
        $result['processed'],
        $result['sent'],
        $result['failed'],
        PHP_EOL
    )
);
