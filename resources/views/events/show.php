<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var \AEFS\Models\Event $event */

?>

<?= component('page-header', [

    'title' => $event->titel,

    'subtitle' => 'Evenement',

    'actions' =>

        component('button', [

            'text' => 'Wijzigen',

            'icon' => 'edit',

            'type' => 'warning',

            'href' => Url::to('/events/' . $event->eventId . '/edit')

        ])

]) ?>

<div class="row">

    <div class="col-lg-8">

        <?= component('card', [

            'title' => 'Algemene gegevens',

            'content' => '

<table class="table table-sm mb-0">

<tr>

    <th width="220">Titel</th>

    <td>' . htmlspecialchars($event->titel) . '</td>

</tr>

<tr>

    <th>Omschrijving</th>

    <td>' . nl2br(htmlspecialchars($event->omschrijving ?? '-')) . '</td>

</tr>

<tr>

    <th>Locatie</th>

    <td>' . htmlspecialchars($event->locatie ?? '-') . '</td>

</tr>

<tr>

    <th>Periode</th>

    <td>' . htmlspecialchars($event->displayDate()) . '</td>

</tr>

<tr>

    <th>Duur</th>

    <td>' . $event->durationDays() . ' dag(en)</td>

</tr>

</table>

'

        ]) ?>

    </div>

    <div class="col-lg-4">

        <?= component('card', [

            'title' => 'Status',

            'content' => '

<table class="table table-sm mb-0">

<tr>

    <th width="150">Actief</th>

    <td>' .

($event->isActive()

    ? '<span class="badge bg-success">Ja</span>'

    : '<span class="badge bg-danger">Nee</span>')

. '</td>

</tr>

<tr>

    <th>Status</th>

    <td>' .

($event->isToday()

    ? '<span class="badge bg-primary">Vandaag</span>'

    : ($event->isFuture()

        ? '<span class="badge bg-info">Toekomstig</span>'

        : '<span class="badge bg-secondary">Afgelopen</span>'))

. '</td>

</tr>

</table>

'

        ]) ?>

    </div>

</div>

<br>

<div class="d-flex justify-content-between">

    <?= component('button', [

        'href' => Url::to('/events'),

        'text' => 'Terug',

        'type' => 'secondary'

    ]) ?>

    <?= component('button', [

        'href' => Url::to('/events/' . $event->eventId . '/edit'),

        'text' => 'Wijzigen',

        'icon' => 'edit',

        'type' => 'warning'

    ]) ?>

</div>