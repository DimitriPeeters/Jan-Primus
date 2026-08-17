<?php

declare(strict_types=1);

use App\Controllers\SettingsController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->get('/settings', [SettingsController::class, 'index'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('settings.index');

$router
    ->post('/settings/update', [SettingsController::class, 'update'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('settings.update');

$router
    ->post(
        '/settings/shift-types/store',
        [SettingsController::class, 'storeShiftType']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('settings.shift-types.store');

$router
    ->post(
        '/settings/shift-types/{id}/update',
        [SettingsController::class, 'updateShiftType']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('settings.shift-types.update');
