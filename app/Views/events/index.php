<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;

/** @var ViewHelpers $helpers */
/** @var Event[] $events */
/** @var string|null $zoekterm */
/** @var bool|null $isAdmin */
/** @var string|null $title */

$events ??= [];
$zoekterm ??= '';
$isAdmin ??= false;

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Evenementen',
    ]
);

$createButton = '';

if ($isAdmin) {
    $createButton = sprintf(
        '<a href="%s" class="btn btn-primary">Nieuw evenement</a>',
        $this->escape($helpers->url->to('/events/create'))
    );
}
?>

<?php $this->startSection('content'); ?>
<div class="event-index-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Evenementen',
            'subtitle' => $isAdmin
                ? 'Beheer concepten, publicaties en afgelopen evenementen.'
                : 'Bekijk de beschikbare en afgelopen evenementen.',
            'actions' => $createButton,
        ]
    ) ?>

    <?= $this->component(
        'card',
        [
            'content' => $this->component(
                'search-box',
                [
                    'action' => $helpers->url->to('/events'),
                    'value' => $zoekterm,
                    'placeholder' => 'Zoek op titel, locatie of beschrijving...',
                    'clearUrl' => $helpers->url->to('/events'),
                ]
            ),
        ]
    ) ?>

    <?= $this->component(
        'card',
        [
            'content' => $this->component(
                'events/table',
                [
                    'events' => $events,
                    'isAdmin' => $isAdmin,
                ]
            ),
        ]
    ) ?>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .event-index-page {
        display: grid;
        gap: 1.25rem;
    }
</style>
<?php $this->endSection(); ?>
