<?php

declare(strict_types=1);

use AEFS\Controllers\HomeController;

/** @var AEFS\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);