<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\Shift;

/** @var ViewHelpers $helpers */
/** @var Event $event */
/** @var Shift[] $shifts */
/** @var string|null $title */

$shifts ??= [];

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Shiftplanning',
    ]
);

$totalRequired = array_sum(
    array_map(
        static fn(Shift $shift): int => $shift->maxPersonen,
        $shifts
    )
);

$totalConfirmed = array_sum(
    array_map(
        static fn(Shift $shift): int => $shift->aantalBevestigd,
        $shifts
    )
);

$totalWaiting = array_sum(
    array_map(
        static fn(Shift $shift): int => $shift->aantalWachtend,
        $shifts
    )
);

$totalReserve = array_sum(
    array_map(
        static fn(Shift $shift): int => $shift->aantalReserve,
        $shifts
    )
);

$understaffed = count(
    array_filter(
        $shifts,
        static fn(Shift $shift): bool => $shift->isActief()
            && !$shift->isVolzet()
    )
);

$actions = sprintf(
    '<a href="%s" class="btn btn-primary">Nieuwe shift</a>',
    $this->escape(
        $helpers->url->to(
            '/shifts/create?event_id=' . $event->eventId
        )
    )
);
?>

<?php $this->startSection('content'); ?>
<div class="shift-planner-page">
    <?= $this->component(
        'page-header',
        [
            'title' => $event->titel,
            'subtitle' => 'Shiftplanning · ' . $event->displayDate(),
            'actions' => $actions,
        ]
    ) ?>

    <div class="shift-planner-layout">
        <div class="shift-planner-list">
            <?php if ($shifts === []): ?>
                <section class="card">
                    <div class="card__body">
                        <?= $this->component(
                            'empty-state',
                            [
                                'title' => 'Nog geen shifts gepland',
                                'text' => 'Maak de eerste shift voor dit evenement aan.',
                            ]
                        ) ?>
                    </div>
                </section>
            <?php else: ?>
                <?php foreach ($shifts as $shift): ?>
                    <article class="card shift-planner-card">
                        <header class="card__header shift-planner-card__header">
                            <div>
                                <span
                                    class="shift-type-label"
                                    style="--shift-color: <?= $this->escape(
                                        $shift->typeKleur ?? '#1E3A8A'
                                    ) ?>"
                                >
                                    <?= $this->escape($shift->displayType()) ?>
                                </span>

                                <h2 class="shift-planner-card__title">
                                    <?= $this->escape($shift->displayNaam()) ?>
                                </h2>

                                <p><?= $this->escape($shift->displayPeriode()) ?></p>
                            </div>

                            <span class="badge <?= $this->escape($shift->statusCssClass()) ?>">
                                <?= $this->escape($shift->statusLabel()) ?>
                            </span>
                        </header>

                        <div class="card__body">
                            <div class="shift-progress-heading">
                                <span>Bezetting</span>
                                <strong>
                                    <?= $shift->aantalBevestigd ?> / <?= $shift->maxPersonen ?>
                                </strong>
                            </div>

                            <div
                                class="shift-progress"
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?= $shift->bezettingsPercentage() ?>"
                            >
                                <span
                                    style="width: <?= $shift->bezettingsPercentage() ?>%"
                                ></span>
                            </div>

                            <div class="shift-planner-counts">
                                <div>
                                    <strong><?= $shift->maxPersonen ?></strong>
                                    <span>gevraagd</span>
                                </div>
                                <div>
                                    <strong><?= $shift->aantalBevestigd ?></strong>
                                    <span>bevestigd</span>
                                </div>
                                <div>
                                    <strong><?= $shift->aantalWachtend ?></strong>
                                    <span>wachtend</span>
                                </div>
                                <div>
                                    <strong><?= $shift->aantalReserve ?></strong>
                                    <span>reserve</span>
                                </div>
                            </div>
                        </div>

                        <footer class="card__footer shift-planner-card__actions">
                            <a
                                href="<?= $this->escape(
                                    $helpers->url->to('/shifts/' . $shift->shiftId)
                                ) ?>"
                                class="btn btn-secondary"
                            >
                                Inschrijvingen
                            </a>

                            <a
                                href="<?= $this->escape(
                                    $helpers->url->to(
                                        '/shifts/' . $shift->shiftId . '/edit'
                                    )
                                ) ?>"
                                class="btn btn-warning"
                            >
                                Wijzigen
                            </a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <aside class="card shift-planner-summary">
            <header class="card__header">
                <h2 class="card__title">Planningsoverzicht</h2>
            </header>

            <div class="card__body">
                <dl class="shift-planner-stats">
                    <div>
                        <dt>Shifts</dt>
                        <dd><?= count($shifts) ?></dd>
                    </div>
                    <div>
                        <dt>Plaatsen gevraagd</dt>
                        <dd><?= $totalRequired ?></dd>
                    </div>
                    <div>
                        <dt>Bevestigd</dt>
                        <dd><?= $totalConfirmed ?></dd>
                    </div>
                    <div>
                        <dt>Wachtend</dt>
                        <dd><?= $totalWaiting ?></dd>
                    </div>
                    <div>
                        <dt>Reserve</dt>
                        <dd><?= $totalReserve ?></dd>
                    </div>
                    <div>
                        <dt>Nog niet volzet</dt>
                        <dd><?= $understaffed ?></dd>
                    </div>
                </dl>
            </div>

            <footer class="card__footer">
                <a
                    href="<?= $this->escape(
                        $helpers->url->to('/events/' . $event->eventId)
                    ) ?>"
                    class="btn btn-secondary btn-block"
                >
                    Naar evenement
                </a>
            </footer>
        </aside>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .shift-planner-page {
        display: grid;
        gap: 1.25rem;
    }

    .shift-planner-layout {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(270px, 0.8fr);
        gap: 1.25rem;
        align-items: start;
    }

    .shift-planner-list {
        display: grid;
        gap: 1rem;
    }

    .shift-planner-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .shift-type-label {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 0.25rem 0.65rem;
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 700;
        background: var(--shift-color);
        border-radius: 999px;
    }

    .shift-planner-card__title {
        margin: 0.8rem 0 0.25rem;
        font-size: 1.15rem;
    }

    .shift-planner-card__header p {
        margin: 0;
        color: var(--color-text-muted);
    }

    .shift-progress-heading {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.55rem;
    }

    .shift-progress {
        overflow: hidden;
        height: 0.65rem;
        background: #e5e7eb;
        border-radius: 999px;
    }

    .shift-progress span {
        display: block;
        height: 100%;
        background: var(--color-success);
        border-radius: inherit;
    }

    .shift-planner-counts {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .shift-planner-counts div {
        display: grid;
        gap: 0.2rem;
        padding: 0.75rem;
        text-align: center;
        background: #f8fafc;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
    }

    .shift-planner-counts strong {
        font-size: 1.15rem;
    }

    .shift-planner-counts span {
        color: var(--color-text-muted);
        font-size: 0.75rem;
    }

    .shift-planner-card__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .shift-planner-summary {
        position: sticky;
        top: 92px;
    }

    .shift-planner-stats {
        display: grid;
        gap: 0;
        margin: 0;
    }

    .shift-planner-stats div {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid var(--color-border);
    }

    .shift-planner-stats div:last-child {
        border-bottom: 0;
    }

    .shift-planner-stats dt {
        color: var(--color-text-muted);
    }

    .shift-planner-stats dd {
        margin: 0;
        font-weight: 700;
    }

    @media (max-width: 960px) {
        .shift-planner-layout {
            grid-template-columns: 1fr;
        }

        .shift-planner-summary {
            position: static;
        }
    }

    @media (max-width: 680px) {
        .shift-planner-counts {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .shift-planner-card__actions {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>
<?php $this->endSection(); ?>
