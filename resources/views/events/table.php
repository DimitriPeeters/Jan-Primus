<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var AEFS\Models\Event[] $events */

$events ??= [];

?>

<?php if (empty($events)): ?>

    <?= component('empty-state', [

        'title' => 'Geen evenementen gevonden',

        'text' => 'Er zijn momenteel geen evenementen beschikbaar.'

    ]) ?>

<?php else: ?>

<table class="table table-hover align-middle">

    <thead>

    <tr>

        <th>Titel</th>

        <th>Periode</th>

        <th>Locatie</th>

        <th class="text-center">Status</th>

        <th class="text-center">Actief</th>

        <th class="text-end" style="width:220px;">Acties</th>

    </tr>

    </thead>

    <tbody>

    <?php foreach ($events as $event): ?>

        <tr>

            <td>

                <strong>

                    <?= htmlspecialchars($event->titel) ?>

                </strong>

            </td>

            <td>

                <?= htmlspecialchars($event->displayDate()) ?>

            </td>

            <td>

                <?= htmlspecialchars($event->locatie ?? '-') ?>

            </td>

            <td class="text-center">

                <?php if ($event->isToday()): ?>

                    <span class="badge bg-primary">

                        Vandaag

                    </span>

                <?php elseif ($event->isFuture()): ?>

                    <span class="badge bg-info">

                        Toekomst

                    </span>

                <?php else: ?>

                    <span class="badge bg-secondary">

                        Afgelopen

                    </span>

                <?php endif; ?>

            </td>

            <td class="text-center">

                <?php if ($event->isActive()): ?>

                    <span class="badge bg-success">

                        Actief

                    </span>

                <?php else: ?>

                    <span class="badge bg-danger">

                        Inactief

                    </span>

                <?php endif; ?>

            </td>

            <td class="text-end">

                <?= component('button', [

                    'href' => Url::to('/events/' . $event->eventId),

                    'icon' => 'eye',

                    'type' => 'secondary',

                    'size' => 'sm',

                    'title' => 'Bekijken'

                ]) ?>

                <?= component('button', [

                    'href' => Url::to('/events/' . $event->eventId . '/edit'),

                    'icon' => 'edit',

                    'type' => 'warning',

                    'size' => 'sm',

                    'title' => 'Wijzigen'

                ]) ?>

                <?= component('button', [

                    'href' => Url::to('/events/' . $event->eventId . '/delete'),

                    'icon' => 'trash',

                    'type' => 'danger',

                    'size' => 'sm',

                    'confirm' => 'Dit evenement verwijderen?'

                ]) ?>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

<?php endif; ?>