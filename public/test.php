<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use AEFS\Core\Url;

echo '<pre>';

echo Url::base();
echo PHP_EOL;

echo Url::to('/members');
echo PHP_EOL;

echo Url::to('/members/create');