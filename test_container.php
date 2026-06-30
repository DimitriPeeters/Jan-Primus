<?php

require 'bootstrap.php';

use AEFS\Core\Container;
use AEFS\Core\Config;
use AEFS\Core\Database;

$config = Container::get(Config::class);
$db = Container::get(Database::class);

echo "<h2>Container werkt!</h2>";

echo "Applicatie: ";
echo $config->get('app.name');

echo "<br>";

echo "Database versie: ";
echo $db->pdo()->query("SELECT VERSION()")->fetchColumn();