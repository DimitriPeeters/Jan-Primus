<?php

use AEFS\Core\Auth;
use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var list<array{label: string, path: string}>|null $navigationItems */

$defaultItems = [
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
    $defaultItems[] = [
        'label' => 'Leden',
        'path' => '/members',
    ];

    $defaultItems[] = [
        'label' => 'Gebruikers',
        'path' => '/users',
    ];
}

$items = $navigationItems ?? $defaultItems;
$normalizedItems = [];

foreach ($items as $item) {
    $label = trim((string) ($item['label'] ?? ''));
    $path = trim((string) ($item['path'] ?? ''));

    if ($label === '' || $path === '') {
        continue;
    }

    $path = '/' . ltrim($path, '/');

    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    $normalizedItems[] = [
        'label' => $label,
        'path' => $path,
    ];
}

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$currentPath = is_string($currentPath)
    ? '/' . ltrim($currentPath, '/')
    : '/';

$scriptName = str_replace(
    '\\',
    '/',
    (string) ($_SERVER['SCRIPT_NAME'] ?? '')
);

$basePath = str_replace(
    '\\',
    '/',
    dirname($scriptName)
);

if (
    $basePath !== '/'
    && $basePath !== '.'
    && str_starts_with($currentPath, $basePath)
) {
    $currentPath = substr(
        $currentPath,
        strlen($basePath)
    );

    $currentPath = '/' . ltrim(
        $currentPath,
        '/'
    );
}

if ($currentPath !== '/') {
    $currentPath = rtrim($currentPath, '/');
}
?>

<aside class="sidebar">
    <div class="sidebar__brand">
        <a
            class="sidebar__brand-link"
            href="<?= $this->escape(
                $helpers->url->to('/dashboard')
            ) ?>"
        >
            <img
                class="sidebar__logo"
                src="<?= $this->escape(
                    $helpers->asset->url(
                        'images/aefs-logo-white.png'
                    )
                ) ?>"
                alt="AEFS"
            >

            <span class="sidebar__brand-text">
                Eventbeheer
            </span>
        </a>
    </div>

    <nav
        class="sidebar__navigation"
        aria-label="Hoofdnavigatie"
    >
        <ul class="sidebar__menu">
            <?php foreach ($normalizedItems as $item): ?>
                <?php
                $itemPath = $item['path'];
                $itemUrl = $helpers->url->to($itemPath);

                $active = $currentPath === $itemPath
                    || (
                        $itemPath !== '/dashboard'
                        && $itemPath !== '/'
                        && str_starts_with(
                            $currentPath,
                            $itemPath . '/'
                        )
                    );
                ?>

                <li class="sidebar__item">
                    <a
                        class="sidebar__link<?= $active
                            ? ' sidebar__link--active'
                            : '' ?>"
                        href="<?= $this->escape($itemUrl) ?>"
                    >
                        <span class="sidebar__link-label">
                            <?= $this->escape($item['label']) ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar__footer">
        <span>AEFS v2</span>
    </div>
</aside>
