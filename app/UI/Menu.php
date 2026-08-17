<?php

declare(strict_types=1);

namespace App\UI;

final class Menu
{
    public static function items(): array
    {
        return require dirname(__DIR__, 2) . '/config/menu.php';
    }
}
