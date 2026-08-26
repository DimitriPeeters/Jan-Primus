<?php

declare(strict_types=1);

use App\Controllers\ShiftController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->get(
        '/shifts',
        [
            ShiftController::class,
            'index',
        ]
    )
    ->middleware(
        AuthMiddleware::class
    )
    ->name('shifts.index');

$router
    ->get(
        '/shifts/create',
        [
            ShiftController::class,
            'create',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.create');

$router
    ->post(
        '/shifts/store',
        [
            ShiftController::class,
            'store',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.store');

$router
    ->get(
        '/shifts/event/{eventId}',
        [
            ShiftController::class,
            'planner',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.planner');

$router
    ->get(
        '/shifts/{id}',
        [
            ShiftController::class,
            'show',
        ]
    )
    ->middleware(
        AuthMiddleware::class
    )
    ->name('shifts.show');

$router
    ->get(
        '/shifts/{id}/edit',
        [
            ShiftController::class,
            'edit',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.edit');

$router
    ->post(
        '/shifts/{id}/update',
        [
            ShiftController::class,
            'update',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.update');

$router
    ->post(
        '/shifts/{id}/cancel',
        [
            ShiftController::class,
            'cancelShift',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.cancel');

$router
    ->post(
        '/shifts/{id}/delete',
        [
            ShiftController::class,
            'destroy',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.destroy');

$router
    ->post(
        '/shifts/{id}/assign',
        [
            ShiftController::class,
            'assign',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shifts.assign');

$router
    ->post(
        '/shifts/{id}/register',
        [ShiftController::class, 'register']
    )
    ->middleware(AuthMiddleware::class)
    ->name('shifts.register');

$router
    ->post(
        '/shifts/{id}/withdraw',
        [ShiftController::class, 'withdraw']
    )
    ->middleware(AuthMiddleware::class)
    ->name('shifts.withdraw');

$router
    ->post(
        '/shift-registrations/{registrationId}/approve',
        [
            ShiftController::class,
            'approve',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shift-registrations.approve');

$router
    ->post(
        '/shift-registrations/{registrationId}/reserve',
        [
            ShiftController::class,
            'reserve',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shift-registrations.reserve');

$router
    ->post(
        '/shift-registrations/{registrationId}/reject',
        [
            ShiftController::class,
            'reject',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shift-registrations.reject');

$router
    ->post(
        '/shift-registrations/{registrationId}/cancel',
        [
            ShiftController::class,
            'cancelRegistration',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shift-registrations.cancel');

$router
    ->post(
        '/shift-registrations/{registrationId}/presence',
        [
            ShiftController::class,
            'presence',
        ]
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('shift-registrations.presence');
