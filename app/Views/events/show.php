<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Shift;

/** @var ViewHelpers $helpers */
/** @var Event $event */
/** @var bool|null $isAdmin */
/** @var bool|null $canParticipate */
/** @var EventRegistration|null $registration */
/** @var EventRegistration[] $registrations */
/** @var Shift[] $shifts */
/** @var string|null $title */

$isAdmin ??= false;
$canParticipate ??= false;
$registration ??= null;
$registrations ??= [];
$shifts ??= [];

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? $event->titel,
    ]
);

$actions = '';

if ($isAdmin) {
    $actions = sprintf(
        '<a href="%s" class="btn btn-primary">Shift toevoegen</a>'
        . '<a href="%s" class="btn btn-warning">Wijzigen</a>',
        $this->escape(
            $helpers->url->to(
                '/shifts/create?event_id=' . $event->eventId
            )
        ),
        $this->escape(
            $helpers->url->to(
                '/events/' . $event->eventId . '/edit'
            )
        )
    );
}

$oldDays = $helpers->old->get('dagen', null);

if (!is_array($oldDays)) {
    $oldDays = $registration?->dagen ?? [];
}

if (
    $registration === null
    && !$event->duurtMeerdereDagen()
    && $oldDays === []
) {
    $oldDays = [$event->startDatum];
}
?>

<?php $this->startSection('content'); ?>
<div class="event-show-page">
    <?= $this->component(
        'page-header',
        [
            'title' => $event->titel,
            'subtitle' => $event->displayDate(),
            'actions' => $actions,
        ]
    ) ?>

    <?php if ($event->isCancelled()): ?>
        <div class="alert alert--error" role="alert">
            Dit evenement werd geannuleerd.
        </div>
    <?php endif; ?>

    <div class="event-show-grid">
        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Evenementgegevens</h2>
            </header>

            <div class="card__body">
                <dl class="event-details">
                    <div>
                        <dt>Periode</dt>
                        <dd><?= $this->escape($event->displayDate()) ?></dd>
                    </div>
                    <div>
                        <dt>Duur</dt>
                        <dd><?= $event->durationDays() ?> dag(en)</dd>
                    </div>
                    <div>
                        <dt>Locatie</dt>
                        <dd><?= $this->escape($event->locatie ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt>Capaciteit</dt>
                        <dd><?= $this->escape($event->capacityLabel()) ?></dd>
                    </div>
                </dl>

                <div class="event-description">
                    <h3>Beschrijving</h3>

                    <?php if ($event->hasDescription()): ?>
                        <p><?= nl2br($this->escape((string) $event->beschrijving)) ?></p>
                    <?php else: ?>
                        <p class="event-muted">Geen beschrijving opgegeven.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <aside class="card">
            <header class="card__header">
                <h2 class="card__title">Status</h2>
            </header>

            <div class="card__body">
                <dl class="event-details event-details--compact">
                    <div>
                        <dt>Periode</dt>
                        <dd>
                            <span class="badge <?= $this->escape($event->periodStatusCssClass()) ?>">
                                <?= $this->escape($event->periodStatusLabel()) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Evenementstatus</dt>
                        <dd>
                            <span class="badge <?= $this->escape($event->statusCssClass()) ?>">
                                <?= $this->escape($event->statusLabel()) ?>
                            </span>
                        </dd>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div>
                            <dt>Inschrijvingen</dt>
                            <dd><?= $event->aantalInschrijvingen ?></dd>
                        </div>
                        <div>
                            <dt>Bevestigd</dt>
                            <dd><?= $event->aantalBevestigd ?></dd>
                        </div>
                        <div>
                            <dt>Openstaande annulaties</dt>
                            <dd><?= $event->aantalAnnulatieverzoeken ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ($registration !== null): ?>
                        <div>
                            <dt>Mijn inschrijving</dt>
                            <dd>
                                <span class="badge <?= $this->escape($registration->statusCssClass()) ?>">
                                    <?= $this->escape($registration->statusLabel()) ?>
                                </span>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </aside>
    </div>

    <?php if ($canParticipate): ?>
        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Mijn deelname</h2>
            </header>

            <div class="card__body event-registration-body">
                <?php if ($registration !== null): ?>
                    <div class="event-registration-summary">
                        <div>
                            <span>Status</span>
                            <strong>
                                <span class="badge <?= $this->escape($registration->statusCssClass()) ?>">
                                    <?= $this->escape($registration->statusLabel()) ?>
                                </span>
                            </strong>
                        </div>
                        <div>
                            <span>Gekozen dagen</span>
                            <strong><?= $this->escape($registration->displayDagen()) ?></strong>
                        </div>
                    </div>

                    <p class="event-muted">
                        Je kunt voor gepubliceerde shifts zelf een voorkeur doorgeven. Een administrator beoordeelt en beheert de definitieve toewijzing.
                    </p>

                    <?php if ($registration->hasPendingCancellation()): ?>
                        <div class="alert alert-warning" role="status">
                            Je annulatieaanvraag van
                            <?= $this->escape($registration->displayAnnulatieAangevraagdOp()) ?>
                            wacht op verificatie door een administrator. Tot dan blijven je huidige shifttoewijzingen zichtbaar.
                        </div>
                    <?php elseif ($registration->isActief() && !$event->isPast()): ?>
                        <form
                            method="post"
                            action="<?= $this->escape(
                                $helpers->url->to(
                                    '/events/' . $event->eventId . '/cancel-registration'
                                )
                            ) ?>"
                            class="event-cancellation-form"
                            onsubmit="return confirm('Wil je jouw inschrijving voor dit evenement annuleren?');"
                        >
                            <?= $helpers->csrf->field() ?>

                            <div class="form-group">
                                <label for="annulatie_reden">Reden van annulering <span class="event-muted">(optioneel)</span></label>
                                <textarea
                                    id="annulatie_reden"
                                    name="annulatie_reden"
                                    rows="3"
                                    maxlength="1000"
                                ></textarea>
                            </div>

                            <button type="submit" class="btn btn-danger">
                                Mijn inschrijving annuleren
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (
                    $event->isPublished()
                    && !$event->isPast()
                    && (
                        $registration === null
                        || $registration->isUitgeschreven()
                        || (
                            $registration->isWachtend()
                            && !$registration->hasPendingCancellation()
                        )
                    )
                ): ?>
                    <form
                        method="post"
                        action="<?= $this->escape(
                            $helpers->url->to(
                                '/events/' . $event->eventId . '/register'
                            )
                        ) ?>"
                        class="event-registration-form"
                        data-event-registration-form
                    >
                        <?= $helpers->csrf->field() ?>

                        <fieldset>
                            <legend>Op welke dagen ben je beschikbaar?</legend>

                            <?php if ($event->duurtMeerdereDagen()): ?>
                                <label class="event-day-option event-day-option--all">
                                    <input type="checkbox" data-select-all-event-days>
                                    <span>Volledige evenement</span>
                                </label>
                            <?php endif; ?>

                            <div class="event-day-grid">
                                <?php foreach ($event->dates() as $date): ?>
                                    <?php $dateLabel = (new DateTimeImmutable($date))->format('d/m/Y'); ?>
                                    <label class="event-day-option">
                                        <input
                                            type="checkbox"
                                            name="dagen[]"
                                            value="<?= $this->escape($date) ?>"
                                            <?= in_array($date, $oldDays, true)
                                                ? 'checked'
                                                : '' ?>
                                            data-event-day
                                        >
                                        <span><?= $this->escape($dateLabel) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <p class="event-muted">
                            Een administrator beoordeelt je deelname en wijst eventuele shifts toe. Je kiest zelf geen shift.
                        </p>

                        <button type="submit" class="btn btn-success">
                            <?= $registration?->isWachtend()
                                ? 'Beschikbaarheid wijzigen'
                                : 'Inschrijven voor evenement' ?>
                        </button>
                    </form>
                <?php elseif ($registration === null): ?>
                    <p class="event-muted">
                        Dit evenement staat momenteel niet open voor inschrijvingen.
                    </p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <section class="card">
            <header class="card__header event-section-header">
                <div>
                    <h2 class="card__title">Evenementinschrijvingen</h2>
                    <p>Beoordeel eerst de gekozen dagen; daarna kan je bevestigde deelnemers aan shifts toewijzen.</p>
                </div>
            </header>

            <div class="card__body event-table-body">
                <?php if ($registrations === []): ?>
                    <?= $this->component(
                        'empty-state',
                        [
                            'title' => 'Nog geen inschrijvingen',
                            'text' => 'Er heeft zich nog niemand voor dit evenement opgegeven.',
                        ]
                    ) ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Lid</th>
                                    <th>Dagen</th>
                                    <th>Status</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $eventRegistration): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $this->escape($eventRegistration->lidNaam()) ?></strong>
                                            <small class="event-table-muted">
                                                <?= $this->escape($eventRegistration->lidEmail ?? '-') ?>
                                            </small>
                                        </td>
                                        <td><?= $this->escape($eventRegistration->displayDagen()) ?></td>
                                        <td>
                                            <span class="badge <?= $this->escape($eventRegistration->statusCssClass()) ?>">
                                                <?= $this->escape($eventRegistration->statusLabel()) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="event-action-stack">
                                                <?php if ($eventRegistration->hasPendingCancellation()): ?>
                                                    <div class="event-cancellation-request">
                                                        <strong>Verificatie vereist</strong>
                                                        <span>
                                                            Aangevraagd op
                                                            <?= $this->escape($eventRegistration->displayAnnulatieAangevraagdOp()) ?>
                                                        </span>

                                                        <?php if ($eventRegistration->uitschrijfreden !== null): ?>
                                                            <span>
                                                                Reden: <?= $this->escape($eventRegistration->uitschrijfreden) ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <form
                                                            method="post"
                                                            action="<?= $this->escape(
                                                                $helpers->url->to(
                                                                    '/event-registrations/'
                                                                    . $eventRegistration->inschrijvingId
                                                                    . '/confirm-cancellation'
                                                                )
                                                            ) ?>"
                                                            onsubmit="return confirm('Deze annulatie verifiëren en alle actieve shifttoewijzingen annuleren?');"
                                                        >
                                                            <?= $helpers->csrf->field() ?>
                                                            <button type="submit" class="btn btn-danger event-small-button">
                                                                Annulering bevestigen
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!$eventRegistration->hasPendingCancellation() && !$eventRegistration->isBevestigd() && $eventRegistration->isActief()): ?>
                                                    <form method="post" action="<?= $this->escape(
                                                        $helpers->url->to(
                                                            '/event-registrations/'
                                                            . $eventRegistration->inschrijvingId
                                                            . '/approve'
                                                        )
                                                    ) ?>">
                                                        <?= $helpers->csrf->field() ?>
                                                        <button type="submit" class="btn btn-success event-small-button">
                                                            Bevestigen
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (!$eventRegistration->hasPendingCancellation() && !$eventRegistration->isReserve() && $eventRegistration->isActief()): ?>
                                                    <form method="post" action="<?= $this->escape(
                                                        $helpers->url->to(
                                                            '/event-registrations/'
                                                            . $eventRegistration->inschrijvingId
                                                            . '/reserve'
                                                        )
                                                    ) ?>">
                                                        <?= $helpers->csrf->field() ?>
                                                        <button type="submit" class="btn btn-warning event-small-button">
                                                            Reserve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (!$eventRegistration->hasPendingCancellation() && !$eventRegistration->isGeweigerd() && $eventRegistration->isActief()): ?>
                                                    <form
                                                        method="post"
                                                        action="<?= $this->escape(
                                                            $helpers->url->to(
                                                                '/event-registrations/'
                                                                . $eventRegistration->inschrijvingId
                                                                . '/reject'
                                                            )
                                                        ) ?>"
                                                        onsubmit="return confirm('Deze evenementinschrijving weigeren?');"
                                                    >
                                                        <?= $helpers->csrf->field() ?>
                                                        <button type="submit" class="btn btn-danger event-small-button">
                                                            Weigeren
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
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <header class="card__header event-section-header">
                <div>
                    <h2 class="card__title">Shifts</h2>
                    <p>Open een shift om bevestigde deelnemers voor de juiste dag toe te wijzen.</p>
                </div>
                <div class="event-section-actions">
                    <a
                        href="<?= $this->escape(
                            $helpers->url->to(
                                '/shifts/event/' . $event->eventId
                            )
                        ) ?>"
                        class="btn btn-secondary"
                    >
                        Volledige planning
                    </a>

                    <form
                        method="post"
                        action="<?= $this->escape(
                            $helpers->url->to(
                                '/events/'
                                . $event->eventId
                                . '/send-shift-planning'
                            )
                        ) ?>"
                        onsubmit="return confirm('De persoonlijke shiftplanning naar alle bevestigde vrijwilligers mailen?');"
                    >
                        <?= $helpers->csrf->field() ?>
                        <button type="submit" class="btn btn-success">
                            Shiftplanning mailen
                        </button>
                    </form>
                </div>
            </header>

            <div class="card__body">
                <?php if ($shifts === []): ?>
                    <?= $this->component(
                        'empty-state',
                        [
                            'title' => 'Nog geen shifts',
                            'text' => 'Voeg een shift toe via het eventformulier of de shiftplanning.',
                        ]
                    ) ?>
                <?php else: ?>
                    <p class="event-planning-status">
                        <?= $event->planningWasSent()
                            ? 'Laatste volledig afgeleverde planning: '
                                . $this->escape($event->displayPlanningSentAt())
                            : 'Er werd nog geen shiftplanning volledig afgeleverd.' ?>
                    </p>

                    <div class="event-shift-list">
                        <?php foreach ($shifts as $shift): ?>
                            <a href="<?= $this->escape(
                                $helpers->url->to('/shifts/' . $shift->shiftId)
                            ) ?>">
                                <strong><?= $this->escape($shift->displayNaam()) ?></strong>
                                <span><?= $this->escape($shift->displayPeriode()) ?></span>
                                <small><?= $shift->aantalBevestigd ?> / <?= $shift->maxPersonen ?> toegewezen</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="event-show-actions">
        <a href="<?= $this->escape($helpers->url->to('/events')) ?>" class="btn btn-secondary">
            Terug naar evenementen
        </a>

        <?php if ($isAdmin): ?>
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
                <button type="submit" class="btn btn-danger">Verwijderen</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .event-show-page,
    .event-registration-body,
    .event-registration-form {
        display: grid;
        gap: 1.25rem;
    }

    .event-cancellation-form,
    .event-cancellation-request {
        display: grid;
        gap: 0.75rem;
    }

    .event-cancellation-form {
        padding-top: 1rem;
        border-top: 1px solid var(--color-border);
    }

    .event-cancellation-form textarea {
        width: 100%;
    }

    .event-cancellation-request {
        min-width: 250px;
        padding: 0.75rem;
        background: #fff7ed;
        border: 1px solid #fdba74;
        border-radius: var(--radius-medium);
    }

    .event-cancellation-request span {
        color: var(--color-text-muted);
        font-size: 0.82rem;
    }

    .event-show-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 1.25rem;
        align-items: start;
    }

    .event-details,
    .event-registration-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin: 0;
    }

    .event-details--compact {
        grid-template-columns: 1fr;
    }

    .event-details div,
    .event-registration-summary div {
        padding: 0.85rem;
        background: #f8fafc;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
    }

    .event-details dt,
    .event-registration-summary span {
        display: block;
        margin-bottom: 0.3rem;
        color: var(--color-text-muted);
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .event-details dd {
        margin: 0;
        font-weight: 600;
    }

    .event-description {
        margin-top: 1.5rem;
    }

    .event-description h3,
    .event-description p,
    .event-section-header p {
        margin: 0;
    }

    .event-section-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .event-planning-status {
        margin: 0 0 1rem;
        color: var(--color-text-muted);
        font-size: 0.88rem;
    }

    .event-description h3 {
        margin-bottom: 0.65rem;
        font-size: 1rem;
    }

    .event-muted,
    .event-section-header p,
    .event-table-muted,
    .event-shift-list span,
    .event-shift-list small {
        color: var(--color-text-muted);
    }

    .event-registration-form fieldset {
        display: grid;
        gap: 0.85rem;
        padding: 0;
        border: 0;
    }

    .event-registration-form legend {
        margin-bottom: 0.8rem;
        font-weight: 700;
    }

    .event-day-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .event-day-option {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.75rem;
        background: #f8fafc;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
        cursor: pointer;
    }

    .event-day-option--all {
        width: max-content;
        background: #eef6ff;
    }

    .event-registration-form .btn {
        width: max-content;
    }

    .event-section-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .event-section-header p {
        margin-top: 0.35rem;
    }

    .event-table-body {
        padding-top: 0;
    }

    .event-table-muted {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.8rem;
    }

    .event-action-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .event-small-button {
        min-height: 32px;
        padding: 0.42rem 0.65rem;
        font-size: 0.78rem;
    }

    .event-shift-list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.8rem;
    }

    .event-shift-list a {
        display: grid;
        gap: 0.3rem;
        padding: 0.9rem;
        color: var(--color-text);
        text-decoration: none;
        background: #f8fafc;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
    }

    .event-shift-list a:hover {
        border-color: var(--color-primary);
    }

    .event-show-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 900px) {
        .event-show-grid,
        .event-details,
        .event-registration-summary,
        .event-shift-list {
            grid-template-columns: 1fr;
        }

        .event-day-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 620px) {
        .event-day-grid {
            grid-template-columns: 1fr;
        }

        .event-section-header,
        .event-show-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .event-registration-form .btn,
        .event-show-actions .btn,
        .event-show-actions form {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('scripts'); ?>
<script>
    document.querySelectorAll('[data-event-registration-form]').forEach((form) => {
        const selectAll = form.querySelector('[data-select-all-event-days]');
        const dayInputs = Array.from(form.querySelectorAll('[data-event-day]'));

        if (!(selectAll instanceof HTMLInputElement) || dayInputs.length === 0) {
            return;
        }

        const updateSelectAll = () => {
            const selectedCount = dayInputs.filter((input) => input.checked).length;
            selectAll.checked = selectedCount === dayInputs.length;
            selectAll.indeterminate = selectedCount > 0
                && selectedCount < dayInputs.length;
        };

        selectAll.addEventListener('change', () => {
            dayInputs.forEach((input) => {
                input.checked = selectAll.checked;
            });
            updateSelectAll();
        });

        dayInputs.forEach((input) => input.addEventListener('change', updateSelectAll));
        updateSelectAll();
    });
</script>
<?php $this->endSection(); ?>
