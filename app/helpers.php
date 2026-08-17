<?php

declare(strict_types=1);

use App\UI\Component;
use App\UI\Icon;

if (!function_exists('component')) {

    function component(
        string $component,
        array $data = []
    ): string {

        return Component::render(
            $component,
            $data
        );

    }

}

if (!function_exists('icon')) {

    function icon(
        string $icon,
        string $class = ''
    ): string {

        return Icon::render(
            $icon,
            $class
        );

    }

}
