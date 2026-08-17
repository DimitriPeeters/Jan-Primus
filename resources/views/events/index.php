<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var AEFS\Models\Event[] $events */
/** @var string $zoekterm */

$zoekterm ??= '';

?>

<?= component('page-header', [

    'title' => 'Evenementen',

    'subtitle' => 'Overzicht van alle evenementen',

    'actions' => component('button', [

        'text' => 'Nieuw evenement',

        'icon' => 'plus',

        'type' => 'primary',

        'href' => Url::to('/events/create'),

    ]),

]) ?>

<?= component('card', [

    'content' => component('search-box', [

        'action' => Url::to('/events'),

        'name' => 'q',

        'value' => $zoekterm,

        'placeholder' => 'Zoek op titel, locatie of omschrijving...',

    ]),

]) ?>

<br>

<?= component('card', [

    'content' => component('events/table', [

        'events' => $events,

    ]),

]) ?>