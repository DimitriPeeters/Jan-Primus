<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\RegistrationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->get('/login', [AuthController::class, 'login'])
    ->middleware(GuestMiddleware::class)
    ->name('login');

$router
    ->post('/login', [AuthController::class, 'authenticate'])
    ->middleware(GuestMiddleware::class)
    ->name('login.attempt');

$router
    ->get('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware(GuestMiddleware::class)
    ->name('password.forgot');

$router
    ->post(
        '/forgot-password',
        [AuthController::class, 'sendPasswordResetLink']
    )
    ->middleware(GuestMiddleware::class)
    ->name('password.email');

$router
    ->get('/reset-password/{token}', [AuthController::class, 'resetPassword'])
    ->middleware(GuestMiddleware::class)
    ->name('password.reset');

$router
    ->post(
        '/reset-password/{token}',
        [AuthController::class, 'updatePassword']
    )
    ->middleware(GuestMiddleware::class)
    ->name('password.update');

$router
    ->get('/register', [RegistrationController::class, 'create'])
    ->middleware(GuestMiddleware::class)
    ->name('register');

$router
    ->post('/register', [RegistrationController::class, 'store'])
    ->middleware(GuestMiddleware::class)
    ->name('register.store');

$router
    ->post('/logout', [AuthController::class, 'logout'])
    ->middleware(AuthMiddleware::class)
    ->name('logout');
