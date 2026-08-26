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
        '/reports/day-attendance',
        [ReportController::class, 'dayAttendance']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.day-attendance');

$router
    ->post(
        '/reports/day-attendance/details',
        [ReportController::class, 'saveDayAttendanceDetails']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.day-attendance.details');

$router
    ->get(
        '/reports/event-participants',
        [ReportController::class, 'eventParticipants']
    )
    ->middleware(
        AuthMiddleware::class,
        AdminMiddleware::class
    )
    ->name('reports.event-participants');
