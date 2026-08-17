<?php

declare(strict_types=1);

use AEFS\Core\Application;
use AEFS\Core\Container;
use AEFS\Core\View\ViewEngineInterface;
use Tests\View\ViewEngineSmokeTest;

$root = dirname(__DIR__);

require $root
    . DIRECTORY_SEPARATOR
    . 'vendor'
    . DIRECTORY_SEPARATOR
    . 'autoload.php';

/** @var Application $app */
$app = require $root
    . DIRECTORY_SEPARATOR
    . 'bootstrap'
    . DIRECTORY_SEPARATOR
    . 'app.php';

/** @var Container $container */
$container = $app->container();

$test = new ViewEngineSmokeTest(
    $container->get(ViewEngineInterface::class)
);

$test->run();

fwrite(
    STDOUT,
    'View Engine smoke test geslaagd.' . PHP_EOL
);