<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use AEFS\Core\Config;
use AEFS\Core\Database;
use AEFS\Core\Logger;
use AEFS\Core\ServiceProvider;
use AEFS\Providers\AppServiceProvider;

AppServiceProvider::register();
ServiceProvider::register();
Config::getInstance();
Database::getInstance();
Logger::getInstance();

