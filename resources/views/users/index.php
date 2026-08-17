<?php

declare(strict_types=1);

use AEFS\Core\Url;

/**
 * @var AEFS\Models\User[] $gebruikers
 * @var string $zoekterm
 */

$zoekterm ??= '';

?>

<?= component('page-header', [

    'title' => 'Gebruikers',

    'subtitle' => 'Overzicht van alle gebruikers',

    'actions' => component('button', [

        'text' => 'Nieuwe gebruiker',

        'icon' => 'plus',

        'type' => 'primary',

        'href' => Url::to('/users/create'),

    ]),

]) ?>

<?= component('card', [

    'content' => component('search-box', [

        'action' => Url::to('/users'),

        'value' => $zoekterm,

        'placeholder' => 'Zoek op naam of e-mailadres...',

    ]),

]) ?>

<br>

<?= component('card', [

    'content' => component('users/table', [

        'gebruikers' => $gebruikers,

    ]),

]) ?>