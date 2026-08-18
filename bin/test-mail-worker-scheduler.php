<?php

declare(strict_types=1);

use AEFS\Core\Config;
use Tests\Mail\MailWorkerSchedulerMiddlewareTest;

$root = dirname(__DIR__);

require $root
    . DIRECTORY_SEPARATOR
    . 'vendor'
    . DIRECTORY_SEPARATOR
    . 'autoload.php';

$test = new MailWorkerSchedulerMiddlewareTest(
    new Config($root . DIRECTORY_SEPARATOR . 'config')
);
$test->run();

fwrite(
    STDOUT,
    'Mailworker-schedulerauthenticatie geslaagd.' . PHP_EOL
);
