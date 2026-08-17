<?php

declare(strict_types=1);

use App\Controllers\HomeController;

/** @var AEFS\Core\Router $router */

require __DIR__ . '/auth.php';
require __DIR__ . '/dashboard.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/members.php';
require __DIR__ . '/users.php';
require __DIR__ . '/events.php';
require __DIR__ . '/shifts.php';
require __DIR__ . '/mailings.php';
require __DIR__ . '/reports.php';
require __DIR__ . '/settings.php';

$router->get('/', [HomeController::class, 'index']);
