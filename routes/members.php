<?php

declare(strict_types=1);

use App\Controllers\MemberController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$memberMiddleware = [
    AuthMiddleware::class,
    AdminMiddleware::class,
];

$router
    ->get('/members', [MemberController::class, 'index'])
    ->middleware(...$memberMiddleware)
    ->name('members.index');

$router
    ->get('/members/{id}', [MemberController::class, 'show'])
    ->middleware(...$memberMiddleware)
    ->name('members.show');

$router
    ->get('/members/{id}/edit', [MemberController::class, 'edit'])
    ->middleware(...$memberMiddleware)
    ->name('members.edit');

$router
    ->post('/members/{id}/update', [MemberController::class, 'update'])
    ->middleware(...$memberMiddleware)
    ->name('members.update');
