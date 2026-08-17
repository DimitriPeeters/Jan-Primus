<?php

declare(strict_types=1);

use AEFS\Core\Container;
use AEFS\Core\View\ViewBootstrapper;
use AEFS\Core\View\ViewServiceProvider;
use App\ViewComposers\ViewComposerServiceProvider;

/** @var Container $container */

$viewConfigFile = dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'config'
    . DIRECTORY_SEPARATOR
    . 'view.php';

if (!is_file($viewConfigFile)) {
    throw new RuntimeException(
        sprintf(
            'Viewconfiguratie [%s] bestaat niet.',
            $viewConfigFile
        )
    );
}

$viewConfig = require $viewConfigFile;

if (!is_array($viewConfig)) {
    throw new RuntimeException(
        'De viewconfiguratie moet een array teruggeven.'
    );
}

$viewBootstrapper = new ViewBootstrapper(
    $container,
    new ViewServiceProvider()
);

$viewBootstrapper->boot($viewConfig);

$composerProvider = new ViewComposerServiceProvider();

$composerProvider->register($container);
$composerProvider->boot($container);