<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use AEFS\Core\Router;

$router = new Router();

require dirname(__DIR__) . '/routes/web.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// verwijder de projectmap uit de URL bij lokale ontwikkeling
$uri = str_replace('/aefs-v2/public', '', $uri);

if ($uri === '') {
    $uri = '/';
}

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $uri
);