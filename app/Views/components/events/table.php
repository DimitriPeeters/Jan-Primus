<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;

/** @var ViewHelpers $helpers */
/** @var Event[] $events */
/** @var bool|null $isAdmin */

$events ??= [];
$isAdmin ??= false;
?>

<?php if ($events === []): ?>
    <?= $this->component(
        'empty-state',
        [
            'title' => 'Geen evenementen gevonden',
            'text' => 'Er zijn geen evenementen die aan de zoekopdracht voldoen.',
        ]
    ) ?>
<?php else: ?>
    <div class="table-responsive event-table-wrapper">
        <table class="table event-table">
            <thead>
                <tr>
                    <th>Evenement</th>
                    <th>Periode</th>
                    <th>Locatie</th>
                    <th>Capaciteit</th>

                    <?php if ($isAdmin): ?>
                        <th>Publicatie</th>
                    <?php endif; ?>

                    <th>Status</th>
                    <th class="event-table__actions-heading">Acties</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td>
                            <strong><?= $this->escape($event->titel) ?></strong>

                            <?php if ($event->hasDescription()): ?>
                                <div class="event-table__description">
                                    <?= $this->escape(
                                        function_exists('mb_strimwidth')
                                            ? mb_strimwidth(
                                                (string) $event->beschrijving,
                                                0,
                                                90,
                                                '…'
                                            )
                                            : substr(
                                                (string) $event->beschrijving,
                                                0,
                                                90
                                            )
                                    ) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($isAdmin && $event->hasPendingCancellationRequests()): ?>
                                <div class="event-table__cancellation-alert">
                                    <?= $event->aantalAnnulatieverzoeken ?> annulatieverzoek(en) te verifiëren
                                </div>
                            <?php endif; ?>
                        </td>

                        <td><?= $this->escape($event->displayDate()) ?></td>
                        <td><?= $this->escape($event->locatie ?? '-') ?></td>

                        <td>
                            <?= $this->escape($event->capacityLabel()) ?>

                            <?php if ($isAdmin): ?>
                                <div class="event-table__description">
                                    <?= $event->aantalInschrijvingen ?> totaal
                                </div>
                            <?php endif; ?>
                        </td>

                        <?php if ($isAdmin): ?>
                            <td>
                                <span class="badge <?= $this->escape($event->statusCssClass()) ?>">
                                    <?= $this->escape($event->statusLabel()) ?>
                                </span>
                            </td>
                        <?php endif; ?>

                        <td>
                            <span class="badge <?= $this->escape($event->periodStatusCssClass()) ?>">
                                <?= $this->escape($event->periodStatusLabel()) ?>
                            </span>
                        </td>

                        <td>
                            <div class="table-actions event-table__actions">
                                <a
                                    href="<?= $this->escape(
                                        $helpers->url->to(
                                            '/events/' . $event->eventId
                                        )
                                    ) ?>"
                                    class="btn btn-secondary btn-sm"
                                >
                                    Bekijken
                                </a>

                                <?php if ($isAdmin): ?>
                                    <a
                                        href="<?= $this->escape(
                                            $helpers->url->to(
                                                '/events/' . $event->eventId . '/edit'
                                            )
                                        ) ?>"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Wijzigen
                                    </a>

                                    <form
                                        method="post"
                                        action="<?= $this->escape(
                                            $helpers->url->to(
                                                '/events/' . $event->eventId . '/delete'
                                            )
                                        ) ?>"
                                        onsubmit="return confirm('Dit evenement definitief verwijderen?');"
                                    >
                                        <?= $helpers->csrf->field() ?>

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Verwijderen
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
        .event-table-wrapper {
            width: 100%;
        }

        .event-table {
            min-width: 960px;
        }

        .event-table__description {
            max-width: 360px;
            margin-top: 0.3rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.35;
        }

        .event-table__cancellation-alert {
            width: max-content;
            margin-top: 0.45rem;
            padding: 0.25rem 0.45rem;
            color: #9a3412;
            font-size: 0.78rem;
            font-weight: 700;
            background: #ffedd5;
            border-radius: var(--radius-small);
        }

        .event-table__actions-heading {
            text-align: right;
        }

        .event-table__actions {
            align-items: center;
            flex-wrap: wrap;
        }

        .event-table__actions form {
            margin: 0;
        }
    </style>
<?php endif; ?>
