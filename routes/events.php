<?php

declare(strict_types=1);

use App\Controllers\EventController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->get('/events', [EventController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->name('events.index');

$router
    ->get('/events/create', [EventController::class, 'create'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('events.create');

$router
    ->post('/events/store', [EventController::class, 'store'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('events.store');

$router
    ->post('/events/{id}/register', [EventController::class, 'register'])
    ->middleware(AuthMiddleware::class)
    ->name('events.register');

$router
    ->post(
        '/events/{id}/cancel-registration',
        [EventController::class, 'cancelRegistration']
    )
    ->middleware(AuthMiddleware::class)
    ->name('events.cancel-registration');

$router
    ->get('/events/{id}', [EventController::class, 'show'])
    ->middleware(AuthMiddleware::class)
    ->name('events.show');

$router
    ->get('/events/{id}/edit', [EventController::class, 'edit'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('events.edit');

$router
    ->post('/events/{id}/update', [EventController::class, 'update'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('events.update');

$router
    ->post('/events/{id}/delete', [EventController::class, 'destroy'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('events.destroy');

$router
    ->post(
        '/event-registrations/{registrationId}/approve',
        [EventController::class, 'approveRegistration']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('event-registrations.approve');

$router
    ->post(
        '/event-registrations/{registrationId}/reserve',
        [EventController::class, 'reserveRegistration']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('event-registrations.reserve');

$router
    ->post(
        '/event-registrations/{registrationId}/reject',
        [EventController::class, 'rejectRegistration']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('event-registrations.reject');

$router
    ->post(
        '/event-registrations/{registrationId}/confirm-cancellation',
        [EventController::class, 'confirmRegistrationCancellation']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('event-registrations.confirm-cancellation');
