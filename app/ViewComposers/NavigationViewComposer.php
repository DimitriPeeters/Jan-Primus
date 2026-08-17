<?php

declare(strict_types=1);

namespace App\ViewComposers;

use AEFS\Core\Auth;
use AEFS\Core\View\Composer\AbstractViewComposer;

final class NavigationViewComposer extends AbstractViewComposer
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function data(
        string $view,
        array $data
    ): array {
        unset($view, $data);

        $items = [
            [
                'label' => 'Dashboard',
                'path' => '/dashboard',
            ],
            [
                'label' => 'Mijn profiel',
                'path' => '/profile',
            ],
        ];

        if (Auth::isAdmin()) {
            $items[] = [
                'label' => 'Leden',
                'path' => '/members',
            ];

            $items[] = [
                'label' => 'Gebruikers',
                'path' => '/users',
            ];
        }

        $items = [
            ...$items,
            [
                'label' => 'Evenementen',
                'path' => '/events',
            ],
            [
                'label' => 'Shiften',
                'path' => '/shifts',
            ],
        ];

        if (Auth::isAdmin()) {
            $items[] = [
                'label' => 'Mailings',
                'path' => '/mailings',
            ];

            $items[] = [
                'label' => 'Rapporten',
                'path' => '/reports',
            ];
        }

        if (Auth::isAdmin()) {
            $items[] = [
                'label' => 'Instellingen',
                'path' => '/settings',
            ];
        }

        return [
            'navigationItems' => $items,
        ];
    }
}
