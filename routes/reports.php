<?php

declare(strict_types=1);

use App\Controllers\ReportController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;

/** @var AEFS\Core\Router $router */

$router
    ->get('/reports', [ReportController::class, 'index'])
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.index');

$router
    ->get(
        '/reports/shift-attendance',
        [ReportController::class, 'shiftAttendance']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.shift-attendance');

$router
    ->get(
        '/reports/event-compensation',
        [ReportController::class, 'eventCompensation']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.event-compensation');

$router
    ->get(
        '/reports/event-compensation/export',
        [ReportController::class, 'eventCompensationExport']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.event-compensation.export');
