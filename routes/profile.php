<?php

declare(strict_types=1);

use App\Controllers\ProfileController;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->get('/profile', [ProfileController::class, 'show'])
    ->middleware(AuthMiddleware::class)
    ->name('profile.show');

$router
    ->get('/profile/edit', [ProfileController::class, 'edit'])
    ->middleware(AuthMiddleware::class)
    ->name('profile.edit');

$router
    ->post('/profile/update', [ProfileController::class, 'update'])
    ->middleware(AuthMiddleware::class)
    ->name('profile.update');
