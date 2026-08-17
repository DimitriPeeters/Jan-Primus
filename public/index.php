<?php

declare(strict_types=1);

use AEFS\Core\Application;
use AEFS\Core\Http\Response;

define('AEFS_START', microtime(true));

$appConfiguration = require dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'config'
    . DIRECTORY_SEPARATOR
    . 'app.php';
$isProduction = ($appConfiguration['environment'] ?? 'production')
    === 'production';

error_reporting(E_ALL);
ini_set('display_errors', $isProduction ? '0' : '1');

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

/** @var Response $response */
$response = $app->run();

$response->send();
