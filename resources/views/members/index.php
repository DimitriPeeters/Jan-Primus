<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var AEFS\Models\Member[] $leden */
/** @var string $zoekterm */

$zoekterm ??= '';

?>

<?= component('page-header', [

    'title' => 'Leden',

    'subtitle' => 'Overzicht van alle leden',

    'actions' => component('button', [

        'text' => 'Nieuw lid',

        'icon' => 'plus',

        'type' => 'primary',

        'href' => Url::to('/members/create'),

    ]),

]) ?>

<?= component('card', [

    'content' => component('search-box', [

        'action' => Url::to('/members'),

        'value' => $zoekterm,

        'placeholder' => 'Zoek op naam, e-mail of gemeente...',

    ]),

]) ?>

<br>

<?= component('card', [

    'content' => component('members/table', [

        'leden' => $leden,

    ]),

]) ?>