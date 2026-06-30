<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use AEFS\Core\Config;
use AEFS\Core\Database;
use AEFS\Core\Logger;

$config = Config::getInstance();

$db = Database::getInstance()->pdo();

Logger::getInstance()->info('Composer autoload werkt.');

echo "<pre>";

echo "Composer OK" . PHP_EOL;
echo "Applicatie : " . $config->get('app.naam') . PHP_EOL;
echo "Versie     : " . $config->get('app.versie') . PHP_EOL;
echo "Database   : " . $db->query("SELECT VERSION()")->fetchColumn() . PHP_EOL;