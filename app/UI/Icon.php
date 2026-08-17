<?php

declare(strict_types=1);

namespace App\UI;

final class Icon
{
    public static function render(
        string $icon,
        string $class = ''
    ): string {

        $file = dirname(__DIR__, 2)
            . '/resources/icons/'
            . $icon
            . '.svg';

        if (!is_file($file)) {

            return '';

        }

        $svg = file_get_contents($file);

        if ($svg === false) {

            return '';

        }

        if ($class !== '') {

            $svg = preg_replace(
                '/<svg\b/',
                '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '"',
                $svg,
                1
            );

        }

        return $svg;
    }
}
