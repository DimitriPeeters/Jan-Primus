<?php

declare(strict_types=1);

use AEFS\Core\Application;
use AEFS\Core\Config;
use AEFS\Core\Container;
use AEFS\Core\Database;
use AEFS\Core\Http\Request;
use AEFS\Core\Kernel;
use AEFS\Core\Router;
use AEFS\Core\Session;
use AEFS\Core\View;
use AEFS\Database\DatabaseManager;
use AEFS\Database\DB;
use App\Mail\Transport\MailTransportInterface;
use App\Mail\Transport\PhpMailerSmtpTransport;

$basePath = dirname(__DIR__);
$sessionPath = $basePath
    . DIRECTORY_SEPARATOR
    . 'storage'
    . DIRECTORY_SEPARATOR
    . 'sessions';

if (is_dir($sessionPath) && is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

$app = new Application($basePath);

$container = $app->container();

$config = new Config(
    $basePath . DIRECTORY_SEPARATOR . 'config'
);

$timezone = $config->get(
    'app.timezone',
    'Europe/Brussels'
);

if (
    !is_string($timezone)
    || $timezone === ''
    || !in_array(
        $timezone,
        timezone_identifiers_list(),
        true
    )
) {
    throw new \RuntimeException(
        'De geconfigureerde applicatietijdzone is ongeldig.'
    );
}

date_default_timezone_set($timezone);

$container->instance(
    Application::class,
    $app
);

$container->instance(
    Container::class,
    $container
);

$container->instance(
    Config::class,
    $config
);

$container->singleton(Database::class);

$container->singleton(
    MailTransportInterface::class,
    PhpMailerSmtpTransport::class
);

$databaseManager = new DatabaseManager(
    $config->get('database', [])
);

DB::setManager($databaseManager);

$container->instance(
    DatabaseManager::class,
    $databaseManager
);

$container->singleton(
    Session::class
);

$container->instance(
    Request::class,
    Request::capture()
);

require __DIR__
    . DIRECTORY_SEPARATOR
    . 'view.php';

$container->singleton(
    View::class
);

$container->singleton(
    Router::class
);

$container->singleton(
    Kernel::class
);

$router = $container->get(
    Router::class
);

require $basePath
    . DIRECTORY_SEPARATOR
    . 'routes'
    . DIRECTORY_SEPARATOR
    . 'web.php';

return $app;
