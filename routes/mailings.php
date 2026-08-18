<?php

declare(strict_types=1);

use App\Controllers\MailController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\MailWorkerSchedulerMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->post(
        '/internal/mail-worker/process',
        [MailController::class, 'processScheduledQueue']
    )
    ->middleware(MailWorkerSchedulerMiddleware::class)
    ->name('mailings.worker.process');

$router
    ->get('/mailings', [MailController::class, 'index'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('mailings.index');

$router
    ->get('/mailings/create', [MailController::class, 'create'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('mailings.create');

$router
    ->post('/mailings/store', [MailController::class, 'store'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('mailings.store');

$router
    ->get('/mailings/{id}', [MailController::class, 'show'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('mailings.show');

$router
    ->post('/mailings/{id}/retry', [MailController::class, 'retry'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('mailings.retry');

$router
    ->post(
        '/events/{id}/send-shift-planning',
        [MailController::class, 'sendShiftPlanning']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('mailings.shift-planning');
