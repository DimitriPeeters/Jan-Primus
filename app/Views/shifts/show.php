<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\EventRegistration;
use App\Models\Shift;
use App\Models\ShiftRegistration;

/** @var ViewHelpers $helpers */
/** @var Shift $shift */
/** @var bool|null $isAdmin */
/** @var ShiftRegistration[] $registrations */
/** @var ShiftRegistration|null $memberRegistration */
/** @var bool|null $memberCanChoose */
/** @var EventRegistration[] $eligibleEventRegistrations */
/** @var string|null $title */

$isAdmin ??= false;
$registrations ??= [];
$memberRegistration ??= null;
$memberCanChoose ??= false;
$eligibleEventRegistrations ??= [];

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? $shift->displayNaam(),
    ]
);

$actions = '';

if ($isAdmin) {
    $actions = sprintf(
        '<a href="%s" class="btn btn-secondary">Aanwezigheidslijst</a> '
        . '<a href="%s" class="btn btn-warning">Wijzigen</a>',
        $this->escape(
            $helpers->url->to(
                '/reports/shift-attendance?shift_id=' . $shift->shiftId
            )
        ),
        $this->escape(
            $helpers->url->to('/shifts/' . $shift->shiftId . '/edit')
        )
    );
}
?>

<?php $this->startSection('content'); ?>
<div class="shift-show-page">
    <?= $this->component(
        'page-header',
        [
            'title' => $shift->displayNaam(),
            'subtitle' => ($shift->eventTitel ?? 'Evenement')
                . ' · '
                . $shift->displayPeriode(),
            'actions' => $actions,
        ]
    ) ?>

    <?php if ($shift->isGeannuleerd()): ?>
        <div class="alert alert-danger" role="alert">
            Deze shift werd geannuleerd. Nieuwe inschrijvingen zijn niet mogelijk.
        </div>
    <?php endif; ?>

    <div class="shift-show-grid">
        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Shiftgegevens</h2>
            </header>

            <div class="card__body">
                <dl class="shift-details">
                    <div>
                        <dt>Evenement</dt>
                        <dd><?= $this->escape($shift->eventTitel ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt>Functie</dt>
                        <dd>
                            <span
                                class="shift-type-label"
                                style="--shift-color: <?= $this->escape(
                                    $shift->typeKleur ?? '#1E3A8A'
                                ) ?>"
                            >
                                <?= $this->escape($shift->displayType()) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Datum</dt>
                        <dd><?= $this->escape($shift->displayDatum()) ?></dd>
                    </div>
                    <div>
                        <dt>Tijdvak</dt>
                        <dd><?= $this->escape($shift->displayTijdvak()) ?></dd>
                    </div>
                    <div>
                        <dt>Duur</dt>
                        <dd><?= $shift->duurInMinuten() ?> minuten</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>
                            <span class="badge <?= $this->escape($shift->statusCssClass()) ?>">
                                <?= $this->escape($shift->statusLabel()) ?>
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <aside class="card">
            <header class="card__header">
                <h2 class="card__title">Bezetting</h2>
            </header>

            <div class="card__body">
                <div class="shift-capacity-number">
                    <strong><?= $shift->aantalBevestigd ?></strong>
                    <span>van <?= $shift->maxPersonen ?> bevestigd</span>
                </div>

                <div
                    class="shift-progress"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="<?= $shift->bezettingsPercentage() ?>"
                >
                    <span style="width: <?= $shift->bezettingsPercentage() ?>%"></span>
                </div>

                <dl class="shift-capacity-list">
                    <div>
                        <dt>Vrije plaatsen</dt>
                        <dd><?= $shift->beschikbarePlaatsen() ?></dd>
                    </div>
                    <div>
                        <dt>Wachtend</dt>
                        <dd><?= $shift->aantalWachtend ?></dd>
                    </div>
                    <div>
                        <dt>Reserve</dt>
                        <dd><?= $shift->aantalReserve ?></dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>

    <?php if (!$isAdmin): ?>
        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Mijn shifttoewijzing</h2>
            </header>

            <div class="card__body">
                <?php if ($memberRegistration !== null): ?>
                    <div class="shift-member-status">
                        <div>
                            <span>Status</span>
                            <strong>
                                <span class="badge <?= $this->escape($memberRegistration->statusCssClass()) ?>">
                                    <?= $this->escape($memberRegistration->statusLabel()) ?>
                                </span>
                            </strong>
                        </div>

                        <div>
                            <span>Toegewezen</span>
                            <strong><?= $this->escape($memberRegistration->displayAangemaaktOp()) ?></strong>
                        </div>
                    </div>

                    <?php if ($memberRegistration->opmerkingLid !== null): ?>
                        <div class="shift-member-comment">
                            <strong>Mijn opmerking</strong>
                            <p><?= nl2br($this->escape($memberRegistration->opmerkingLid)) ?></p>
                        </div>
                    <?php endif; ?>

                    <p class="shift-assignment-note">
                        Een administrator kan deze keuze bevestigen, wijzigen, op reserve zetten of weigeren.
                    </p>

                    <?php if ($memberRegistration->isActief()): ?>
                        <form method="post" action="<?= $this->escape(
                            $helpers->url->to('/shifts/' . $shift->shiftId . '/withdraw')
                        ) ?>" onsubmit="return confirm('Wil je deze shiftkeuze intrekken?');">
                            <?= $helpers->csrf->field() ?>
                            <button type="submit" class="btn btn-secondary">
                                Keuze intrekken
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (
                    $memberCanChoose
                    && ($memberRegistration === null || !$memberRegistration->isActief())
                ): ?>
                    <form method="post" action="<?= $this->escape(
                        $helpers->url->to('/shifts/' . $shift->shiftId . '/register')
                    ) ?>" class="shift-assignment-form">
                        <?= $helpers->csrf->field() ?>
                        <div class="form-group">
                            <label for="opmerking_lid" class="form-label">
                                Opmerking voor de planner <span class="shift-cell-muted">(optioneel)</span>
                            </label>
                            <textarea id="opmerking_lid" name="opmerking_lid"
                                      class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">
                            Deze shift kiezen
                        </button>
                    </form>
                <?php elseif ($memberRegistration === null): ?>
                    <p class="shift-assignment-note">
                        Je kunt deze shift alleen kiezen wanneer je voor het evenement en deze eventdag bent ingeschreven.
                    </p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <?php if ($shift->isActief()): ?>
            <section class="card">
                <header class="card__header">
                    <h2 class="card__title">Vrijwilliger toewijzen</h2>
                </header>

                <div class="card__body">
                    <?php if ($eligibleEventRegistrations === []): ?>
                        <?= $this->component(
                            'empty-state',
                            [
                                'title' => 'Geen beschikbare deelnemers',
                                'text' => 'Er zijn geen actieve evenementinschrijvingen voor deze dag, of alle geschikte deelnemers zijn al toegewezen.',
                            ]
                        ) ?>
                    <?php else: ?>
                        <form
                            method="post"
                            action="<?= $this->escape(
                                $helpers->url->to(
                                    '/shifts/' . $shift->shiftId . '/assign'
                                )
                            ) ?>"
                            class="shift-assignment-form"
                        >
                            <?= $helpers->csrf->field() ?>

                            <div class="form-group">
                                <label for="lid_id" class="form-label">
                                    Vrijwilliger
                                </label>
                                <select id="lid_id" name="lid_id" class="form-control" required>
                                    <option value="">Kies een ingeschreven deelnemer</option>
                                    <?php foreach ($eligibleEventRegistrations as $eventRegistration): ?>
                                        <option value="<?= $eventRegistration->lidId ?>">
                                            <?= $this->escape($eventRegistration->lidNaam()) ?>
                                            · <?= $this->escape($eventRegistration->displayDagen()) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="assignment_status" class="form-label">
                                    Toewijzing
                                </label>
                                <select
                                    id="assignment_status"
                                    name="status"
                                    class="form-control"
                                    required
                                >
                                    <option
                                        value="<?= ShiftRegistration::STATUS_BEVESTIGD ?>"
                                        <?= $shift->isVolzet() ? 'disabled' : '' ?>
                                    >
                                        Bevestigd<?= $shift->isVolzet() ? ' · shift volzet' : '' ?>
                                    </option>
                                    <option
                                        value="<?= ShiftRegistration::STATUS_RESERVE ?>"
                                        <?= $shift->isVolzet() ? 'selected' : '' ?>
                                    >
                                        Reserve
                                    </option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success">
                                Toewijzen
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Shifttoewijzingen</h2>
            </header>

            <div class="card__body shift-registration-table-body">
                <?php if ($registrations === []): ?>
                    <?= $this->component(
                        'empty-state',
                        [
                            'title' => 'Nog geen toewijzingen',
                            'text' => 'Voor deze shift zijn nog geen vrijwilligers toegewezen.',
                        ]
                    ) ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table shift-registration-table">
                            <thead>
                                <tr>
                                    <th>Vrijwilliger</th>
                                    <th>Status</th>
                                    <th>Opmerking</th>
                                    <th>Aanwezig</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $registration): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $this->escape($registration->lidNaam()) ?></strong>
                                            <small class="shift-cell-muted">
                                                <?= $this->escape($registration->lidEmail ?? '-') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $this->escape($registration->statusCssClass()) ?>">
                                                <?= $this->escape($registration->statusLabel()) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $registration->opmerkingLid !== null
                                                ? nl2br($this->escape($registration->opmerkingLid))
                                                : '<span class="shift-cell-muted">Geen</span>' ?>
                                        </td>
                                        <td>
                                            <?php if ($registration->isBevestigd()): ?>
                                                <form
                                                    method="post"
                                                    action="<?= $this->escape(
                                                        $helpers->url->to(
                                                            '/shift-registrations/'
                                                            . $registration->inschrijvingId
                                                            . '/presence'
                                                        )
                                                    ) ?>"
                                                    class="shift-presence-form"
                                                    data-presence-form
                                                >
                                                    <?= $helpers->csrf->field() ?>
                                                    <input
                                                        type="hidden"
                                                        name="aanwezig"
                                                        value="<?= $registration->aanwezig ? '0' : '1' ?>"
                                                    >
                                                    <button
                                                        type="submit"
                                                        class="btn <?= $registration->aanwezig
                                                            ? 'btn-success'
                                                            : 'btn-secondary' ?> shift-small-button"
                                                        aria-pressed="<?= $registration->aanwezig
                                                            ? 'true'
                                                            : 'false' ?>"
                                                        data-presence-button
                                                    >
                                                        <?= $registration->aanwezig
                                                            ? 'Aanwezig'
                                                            : 'Markeren' ?>
                                                    </button>
                                                    <small
                                                        class="shift-presence-feedback"
                                                        role="status"
                                                        aria-live="polite"
                                                        data-presence-feedback
                                                        hidden
                                                    ></small>
                                                </form>
                                            <?php else: ?>
                                                <span class="shift-cell-muted">Niet van toepassing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="shift-action-stack">
                                                <?php if ($registration->isWachtend() || $registration->isReserve()): ?>
                                                    <?php if (!$shift->isVolzet()): ?>
                                                        <form
                                                            method="post"
                                                            action="<?= $this->escape(
                                                                $helpers->url->to(
                                                                    '/shift-registrations/'
                                                                    . $registration->inschrijvingId
                                                                    . '/approve'
                                                                )
                                                            ) ?>"
                                                        >
                                                            <?= $helpers->csrf->field() ?>
                                                            <button type="submit" class="btn btn-success shift-small-button">
                                                                Bevestigen
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if ($registration->isWachtend()): ?>
                                                    <form
                                                        method="post"
                                                        action="<?= $this->escape(
                                                            $helpers->url->to(
                                                                '/shift-registrations/'
                                                                . $registration->inschrijvingId
                                                                . '/reserve'
                                                            )
                                                        ) ?>"
                                                    >
                                                        <?= $helpers->csrf->field() ?>
                                                        <button type="submit" class="btn btn-warning shift-small-button">
                                                            Reserve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($registration->isWachtend() || $registration->isReserve()): ?>
                                                    <form
                                                        method="post"
                                                        action="<?= $this->escape(
                                                            $helpers->url->to(
                                                                '/shift-registrations/'
                                                                . $registration->inschrijvingId
                                                                . '/reject'
                                                            )
                                                        ) ?>"
                                                        onsubmit="return confirm('Deze inschrijving weigeren?');"
                                                    >
                                                        <?= $helpers->csrf->field() ?>
                                                        <button type="submit" class="btn btn-danger shift-small-button">
                                                            Weigeren
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($registration->isActief()): ?>
                                                    <form
                                                        method="post"
                                                        action="<?= $this->escape(
                                                            $helpers->url->to(
                                                                '/shift-registrations/'
                                                                . $registration->inschrijvingId
                                                                . '/cancel'
                                                            )
                                                        ) ?>"
                                                        onsubmit="return confirm('Deze shiftinschrijving annuleren?');"
                                                    >
                                                        <?= $helpers->csrf->field() ?>
                                                        <input
                                                            type="hidden"
                                                            name="annulatie_reden"
                                                            value="Geannuleerd door een administrator."
                                                        >
                                                        <button type="submit" class="btn btn-secondary shift-small-button">
                                                            Annuleren
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

        <?php if ($shift->isActief()): ?>
            <section class="card shift-danger-zone">
                <header class="card__header">
                    <h2 class="card__title">Shift annuleren</h2>
                </header>

                <div class="card__body">
                    <p>
                        Alle wachtende, bevestigde en reserve-inschrijvingen worden eveneens geannuleerd.
                    </p>

                    <form
                        method="post"
                        action="<?= $this->escape(
                            $helpers->url->to('/shifts/' . $shift->shiftId . '/cancel')
                        ) ?>"
                        class="shift-cancel-form"
                        onsubmit="return confirm('Deze volledige shift annuleren?');"
                    >
                        <?= $helpers->csrf->field() ?>

                        <div class="form-group">
                            <label for="shift_annulatie_reden" class="form-label">
                                Reden
                            </label>
                            <textarea
                                id="shift_annulatie_reden"
                                name="annulatie_reden"
                                rows="3"
                                maxlength="1000"
                                class="form-control"
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            Volledige shift annuleren
                        </button>
                    </form>
                </div>
            </section>
        <?php elseif ($registrations === []): ?>
            <form
                method="post"
                action="<?= $this->escape(
                    $helpers->url->to('/shifts/' . $shift->shiftId . '/delete')
                ) ?>"
                onsubmit="return confirm('Deze shift definitief verwijderen?');"
            >
                <?= $helpers->csrf->field() ?>
                <button type="submit" class="btn btn-danger">
                    Shift definitief verwijderen
                </button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <div class="shift-show-footer">
        <a
            href="<?= $this->escape($helpers->url->to('/shifts')) ?>"
            class="btn btn-secondary"
        >
            Terug naar shiftplanning
        </a>

        <?php if ($isAdmin): ?>
            <a
                href="<?= $this->escape(
                    $helpers->url->to('/shifts/event/' . $shift->eventId)
                ) ?>"
                class="btn btn-primary"
            >
                Evenementplanning
            </a>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .shift-show-page {
        display: grid;
        gap: 1.25rem;
    }

    .shift-show-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 1.25rem;
        align-items: start;
    }

    .shift-details {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin: 0;
    }

    .shift-details div,
    .shift-member-status div {
        padding: 0.85rem;
        background: #f8fafc;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
    }

    .shift-details dt,
    .shift-member-status span,
    .shift-capacity-list dt {
        margin-bottom: 0.3rem;
        color: var(--color-text-muted);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .shift-details dd,
    .shift-capacity-list dd {
        margin: 0;
        font-weight: 600;
    }

    .shift-type-label {
        display: inline-flex;
        padding: 0.3rem 0.65rem;
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 700;
        background: var(--shift-color);
        border-radius: 999px;
    }

    .shift-capacity-number {
        display: grid;
        gap: 0.15rem;
        margin-bottom: 0.8rem;
    }

    .shift-capacity-number strong {
        font-size: 2rem;
    }

    .shift-capacity-number span,
    .shift-cell-muted {
        display: block;
        color: var(--color-text-muted);
        font-size: 0.82rem;
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

    .shift-capacity-list {
        display: grid;
        gap: 0;
        margin: 1rem 0 0;
    }

    .shift-capacity-list div {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--color-border);
    }

    .shift-capacity-list div:last-child {
        border-bottom: 0;
    }

    .shift-member-status {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .shift-member-status strong {
        display: block;
    }

    .shift-member-comment {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
    }

    .shift-member-comment p {
        margin: 0.5rem 0 0;
    }

    .shift-registration-form,
    .shift-cancel-form,
    .shift-assignment-form {
        display: grid;
        gap: 1rem;
    }

    .shift-assignment-form {
        grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr) auto;
        align-items: end;
    }

    .shift-assignment-form .form-group {
        margin: 0;
    }

    .shift-assignment-note {
        margin: 1rem 0 0;
        color: var(--color-text-muted);
    }

    .shift-cancel-form {
        margin-top: 1rem;
    }

    .shift-cancel-form .btn,
    .shift-registration-form .btn {
        width: max-content;
    }

    .shift-registration-table-body {
        padding-top: 0;
    }

    .shift-registration-table td:first-child {
        min-width: 190px;
    }

    .shift-registration-table td:nth-child(3) {
        min-width: 190px;
        max-width: 320px;
    }

    .shift-action-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        min-width: 260px;
    }

    .shift-small-button {
        min-height: 32px;
        padding: 0.42rem 0.65rem;
        font-size: 0.78rem;
    }

    .shift-presence-form {
        display: grid;
        gap: 0.4rem;
        justify-items: start;
    }

    .shift-presence-feedback {
        max-width: 220px;
        color: var(--color-text-muted);
        font-size: 0.75rem;
        line-height: 1.35;
    }

    .shift-presence-feedback[data-state="success"] {
        color: var(--color-success);
    }

    .shift-presence-feedback[data-state="error"] {
        color: var(--color-error);
    }

    .shift-danger-zone {
        border-color: #fecaca;
    }

    .shift-danger-zone p {
        margin-top: 0;
    }

    .shift-show-footer {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 900px) {
        .shift-show-grid,
        .shift-details,
        .shift-assignment-form {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .shift-member-status {
            grid-template-columns: 1fr;
        }

        .shift-show-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .shift-show-footer .btn,
        .shift-cancel-form .btn,
        .shift-registration-form .btn {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('scripts'); ?>
<script>
    document.querySelectorAll('[data-presence-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('[data-presence-button]');
            const input = form.querySelector('input[name="aanwezig"]');
            const feedback = form.querySelector('[data-presence-feedback]');

            if (!(button instanceof HTMLButtonElement)
                || !(input instanceof HTMLInputElement)
                || !(feedback instanceof HTMLElement)
            ) {
                form.submit();
                return;
            }

            button.disabled = true;
            feedback.hidden = true;
            feedback.removeAttribute('data-state');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const contentType = response.headers.get('content-type') ?? '';

                if (!contentType.includes('application/json')) {
                    throw new Error(
                        'De aanwezigheidsstatus kon niet worden bijgewerkt. Probeer opnieuw.'
                    );
                }

                const result = await response.json();

                if (!response.ok || result.success !== true) {
                    throw new Error(
                        typeof result.message === 'string'
                            ? result.message
                            : 'De aanwezigheidsstatus kon niet worden bijgewerkt.'
                    );
                }

                const present = result.present === true;

                input.value = present ? '0' : '1';
                button.textContent = present ? 'Aanwezig' : 'Markeren';
                button.classList.toggle('btn-success', present);
                button.classList.toggle('btn-secondary', !present);
                button.setAttribute('aria-pressed', present ? 'true' : 'false');

                feedback.textContent = result.message;
                feedback.dataset.state = 'success';
                feedback.hidden = false;
            } catch (error) {
                feedback.textContent = error instanceof Error
                    ? error.message
                    : 'De aanwezigheidsstatus kon niet worden bijgewerkt.';
                feedback.dataset.state = 'error';
                feedback.hidden = false;
            } finally {
                button.disabled = false;
            }
        });
    });
</script>
<?php $this->endSection(); ?>
