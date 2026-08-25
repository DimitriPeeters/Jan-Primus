<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicPath = realpath(
    __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, (string) $path)
);

if (
    $path !== '/'
    && $publicPath !== false
    && str_starts_with($publicPath, realpath(__DIR__) . DIRECTORY_SEPARATOR)
    && is_file($publicPath)
) {
    $contentTypes = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
    ];
    $extension = strtolower(pathinfo($publicPath, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
    readfile($publicPath);
    exit;
}

require __DIR__ . '/index.php';
