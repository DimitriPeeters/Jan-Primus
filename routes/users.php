<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$userMiddleware = [
    AuthMiddleware::class,
    AdminMiddleware::class,
];

$router
    ->get('/users', [UserController::class, 'index'])
    ->middleware(...$userMiddleware)
    ->name('users.index');

$router
    ->get('/users/{id}', [UserController::class, 'show'])
    ->middleware(...$userMiddleware)
    ->name('users.show');

$router
    ->get('/users/{id}/edit', [UserController::class, 'edit'])
    ->middleware(...$userMiddleware)
    ->name('users.edit');

$router
    ->post('/users/{id}/approve', [UserController::class, 'approve'])
    ->middleware(...$userMiddleware)
    ->name('users.approve');

$router
    ->post('/users/{id}/update', [UserController::class, 'update'])
    ->middleware(...$userMiddleware)
    ->name('users.update');