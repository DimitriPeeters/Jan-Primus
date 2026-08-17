<?php

declare(strict_types=1);

namespace App\UI;

final class Component
{
    /**
     * Render een component of partial.
     *
     * Voorbeelden:
     *
     * component('button')
     * component('card')
     * component('members/form')
     * component('users/form')
     * component('dashboard/widget')
     * component('layout/sidebar')
     */
    public static function render(
        string $component,
        array $data = []
    ): string {

        $base = dirname(__DIR__, 2) . '/resources/views/';

        $locations = [

            $base . 'components/' . $component . '.php',

            $base . $component . '.php',

        ];

        $file = null;

        foreach ($locations as $candidate) {

            if (is_file($candidate)) {

                $file = $candidate;

                break;

            }

        }

        if ($file === null) {

            throw new \RuntimeException(
                "Component '{$component}' niet gevonden."
            );

        }

        extract($data, EXTR_SKIP);

        ob_start();

        require $file;

        return ob_get_clean() ?: '';
    }
}
