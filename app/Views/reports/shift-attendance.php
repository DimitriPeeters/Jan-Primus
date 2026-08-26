<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Shift;
use App\Models\ShiftRegistration;

/** @var ViewHelpers $helpers */
/** @var Shift[] $shifts */
/** @var int $selectedShiftId */
/** @var Shift|null $shift */
/** @var ShiftRegistration[] $registrations */
/** @var string|null $title */
/** @var string|null $applicationName */

$shifts ??= [];
$selectedShiftId ??= 0;
$shift ??= null;
$registrations ??= [];

$actions = sprintf(
    '<a class="btn btn-secondary" href="%s">Terug naar rapporten</a>',
    $this->escape($helpers->url->to('/reports'))
);

if ($shift !== null) {
    $actions .= ' <button class="btn btn-primary" type="button" data-print-report>Afdrukken</button>';
}

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Aanwezigheidslijst per shift',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="shift-attendance-page">
    <div class="report-screen-only">
        <?= $this->component(
            'page-header',
            [
                'title' => 'Aanwezigheidslijst per shift',
                'subtitle' => 'Alleen bevestigde vrijwilligers worden in de lijst opgenomen.',
                'actions' => $actions,
            ]
        ) ?>

        <section class="card report-filter-card">
            <div class="card__body">
                <?php if ($shifts === []): ?>
                    <?= $this->component(
                        'empty-state',
                        [
                            'title' => 'Geen shifts beschikbaar',
                            'text' => 'Maak eerst een shift aan voordat je een aanwezigheidslijst opent.',
                        ]
                    ) ?>
                <?php else: ?>
                    <form
                        method="get"
                        action="<?= $this->escape(
                            $helpers->url->to('/reports/shift-attendance')
                        ) ?>"
                        class="report-filter-form"
                    >
                        <div class="form-group report-filter-field">
                            <label class="form-label" for="shift_id">
                                Shift
                            </label>
                            <select
                                class="form-control"
                                id="shift_id"
                                name="shift_id"
                                required
                            >
                                <option value="">Kies een shift</option>
                                <?php foreach ($shifts as $availableShift): ?>
                                    <?php
                                    $optionLabel = sprintf(
                                        '%s · %s · %s · %d bevestigd',
                                        $availableShift->eventTitel ?? 'Evenement',
                                        $availableShift->displayNaam(),
                                        $availableShift->displayPeriode(),
                                        $availableShift->aantalBevestigd
                                    );

                                    if ($availableShift->isGeannuleerd()) {
                                        $optionLabel .= ' · geannuleerd';
                                    }
                                    ?>
                                    <option
                                        value="<?= $availableShift->shiftId ?>"
                                        <?= $availableShift->shiftId === $selectedShiftId
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $this->escape($optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button class="btn btn-primary" type="submit">
                            Lijst tonen
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if ($shift === null): ?>
        <?php if ($shifts !== []): ?>
            <div class="report-screen-only">
                <?= $this->component(
                    'empty-state',
                    [
                        'title' => 'Selecteer een shift',
                        'text' => 'Kies hierboven de shift waarvoor je de aanwezigheidslijst wilt maken.',
                    ]
                ) ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <section class="attendance-sheet" aria-labelledby="attendance-title">
            <header class="attendance-sheet__header">
                <div>
                    <span class="attendance-sheet__brand">
                        <?= $this->escape($applicationName ?? 'Ledenbeheer') ?>
                    </span>
                    <h1 id="attendance-title">Aanwezigheidslijst</h1>
                    <p><?= $this->escape($shift->eventTitel ?? 'Evenement') ?></p>
                </div>

                <span class="badge <?= $this->escape($shift->statusCssClass()) ?> report-screen-only">
                    <?= $this->escape($shift->statusLabel()) ?>
                </span>
            </header>

            <dl class="attendance-meta">
                <div>
                    <dt>Shift</dt>
                    <dd><?= $this->escape($shift->displayNaam()) ?></dd>
                </div>
                <div>
                    <dt>Functie</dt>
                    <dd><?= $this->escape($shift->displayType()) ?></dd>
                </div>
                <div>
                    <dt>Datum</dt>
                    <dd><?= $this->escape($shift->displayDatum()) ?></dd>
                </div>
                <div>
                    <dt>Tijd</dt>
                    <dd><?= $this->escape($shift->displayTijdvak()) ?></dd>
                </div>
                <div>
                    <dt>Bevestigd</dt>
                    <dd><?= count($registrations) ?> / <?= $shift->maxPersonen ?></dd>
                </div>
            </dl>

            <?php if ($registrations === []): ?>
                <div class="attendance-empty">
                    Er zijn voor deze shift geen bevestigde vrijwilligers.
                </div>
            <?php else: ?>
                <div class="table-responsive attendance-table-wrapper">
                    <table class="table attendance-table">
                        <thead>
                            <tr>
                                <th scope="col">Naam</th>
                                <th scope="col">Voornaam</th>
                                <th scope="col" class="attendance-column">Aanwezig</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $registration): ?>
                                <?php
                                $memberName = trim(
                                    sprintf(
                                        '%s %s',
                                        $registration->lidVoornaam ?? '',
                                        $registration->lidAchternaam ?? ''
                                    )
                                );
                                ?>
                                <tr>
                                    <td><?= $this->escape(
                                        $registration->lidAchternaam ?? '—'
                                    ) ?></td>
                                    <td><?= $this->escape(
                                        $registration->lidVoornaam ?? '—'
                                    ) ?></td>
                                    <td class="attendance-column">
                                        <form
                                            method="post"
                                            action="<?= $this->escape(
                                                $helpers->url->to(
                                                    '/shift-registrations/'
                                                    . $registration->inschrijvingId
                                                    . '/presence'
                                                )
                                            ) ?>"
                                            class="attendance-presence-form"
                                            data-attendance-presence-form
                                        >
                                            <?= $helpers->csrf->field() ?>

                                            <label class="attendance-checkbox">
                                                <input
                                                    type="checkbox"
                                                    name="aanwezig"
                                                    value="1"
                                                    aria-label="<?= $this->escape(
                                                        'Aanwezig: ' . $memberName
                                                    ) ?>"
                                                    data-attendance-presence-checkbox
                                                    <?= $registration->aanwezig
                                                        ? 'checked'
                                                        : '' ?>
                                                >
                                                <span aria-hidden="true"></span>
                                            </label>

                                            <noscript>
                                                <button class="btn btn-sm btn-secondary" type="submit">
                                                    Opslaan
                                                </button>
                                            </noscript>

                                            <span
                                                class="attendance-presence-feedback"
                                                role="status"
                                                aria-live="polite"
                                                data-attendance-presence-feedback
                                            ></span>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p class="attendance-sheet__note">
                Wijzigingen aan de vakjes worden onmiddellijk in Ledenbeheer opgeslagen.
                Op de afgedrukte lijst kunnen ze ook handmatig worden aangevuld.
            </p>
        </section>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .shift-attendance-page {
        display: grid;
        gap: 1.25rem;
    }

    .report-filter-form {
        display: flex;
        align-items: flex-end;
        gap: 1rem;
    }

    .report-filter-field {
        flex: 1;
        margin: 0;
    }

    .attendance-sheet {
        padding: 1.75rem;
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-large);
        box-shadow: var(--shadow-card);
    }

    .attendance-sheet__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 1.25rem;
        border-bottom: 2px solid var(--color-primary);
    }

    .attendance-sheet__brand {
        display: block;
        margin-bottom: 0.35rem;
        color: var(--color-primary);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .attendance-sheet__header h1,
    .attendance-sheet__header p {
        margin: 0;
    }

    .attendance-sheet__header h1 {
        font-size: 1.65rem;
    }

    .attendance-sheet__header p {
        margin-top: 0.35rem;
        color: var(--color-text-muted);
    }

    .attendance-meta {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1rem;
        margin: 1.25rem 0;
    }

    .attendance-meta div {
        min-width: 0;
    }

    .attendance-meta dt {
        margin-bottom: 0.25rem;
        color: var(--color-text-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
    }

    .attendance-meta dd {
        margin: 0;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .attendance-table-wrapper {
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
    }

    .attendance-table {
        width: 100%;
        table-layout: fixed;
    }

    .attendance-table th,
    .attendance-table td {
        font-size: 0.95rem;
        overflow-wrap: anywhere;
    }

    .attendance-column {
        width: 170px;
        text-align: center !important;
    }

    .attendance-presence-form {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 0;
    }

    .attendance-checkbox {
        position: relative;
        display: inline-flex;
        width: 44px;
        height: 44px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        touch-action: manipulation;
    }

    .attendance-checkbox input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .attendance-checkbox span {
        display: flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 2px solid #64748b;
        border-radius: 4px;
    }

    .attendance-checkbox input:focus-visible + span {
        outline: 3px solid rgb(190 18 60 / 25%);
        outline-offset: 2px;
    }

    .attendance-checkbox input:disabled + span {
        cursor: wait;
        opacity: 0.55;
    }

    .attendance-checkbox input:checked + span::after {
        color: #166534;
        content: '✓';
        font-size: 1rem;
        font-weight: 900;
        line-height: 1;
    }

    .attendance-presence-feedback {
        min-width: 0;
        color: #166534;
        font-size: 0.72rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .attendance-presence-feedback.is-error {
        color: #b91c1c;
    }

    .attendance-empty {
        padding: 2rem;
        color: var(--color-text-muted);
        text-align: center;
        background: #f8fafc;
        border: 1px dashed var(--color-border-strong);
        border-radius: var(--radius-medium);
    }

    .attendance-sheet__note {
        margin: 1rem 0 0;
        color: var(--color-text-muted);
        font-size: 0.8rem;
    }

    @media (max-width: 1200px) {
        .attendance-sheet {
            padding: 1.25rem;
        }

        .attendance-meta {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .attendance-table th,
        .attendance-table td {
            padding: 0.85rem 0.75rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 900px) {
        .attendance-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .report-filter-form .btn {
            min-height: 44px;
        }
    }

    @media (max-width: 640px) {
        .report-filter-form {
            align-items: stretch;
            flex-direction: column;
        }

        .attendance-sheet {
            padding: 1rem;
        }

        .attendance-meta {
            grid-template-columns: 1fr;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 0.75rem 0.5rem;
            font-size: 0.92rem;
        }

        .attendance-column {
            width: 104px;
        }

        .attendance-presence-form {
            gap: 0.25rem;
        }

        .attendance-presence-feedback {
            flex-basis: 100%;
        }
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            color: #000;
            background: #fff;
        }

        .sidebar,
        .app-header,
        .footer,
        .report-screen-only {
            display: none !important;
        }

        .app,
        .app__main {
            display: block;
            min-height: 0;
            margin: 0;
        }

        .app__content {
            padding: 0;
        }

        .attendance-sheet {
            padding: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .attendance-sheet__header {
            border-color: #000;
        }

        .attendance-meta {
            gap: 6mm;
        }

        .attendance-table-wrapper {
            overflow: visible;
            border: 0;
            border-radius: 0;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 7px 9px;
            color: #000;
            border: 1px solid #000;
        }

        .attendance-table th {
            background: #fff;
        }

        .attendance-table tr {
            break-inside: avoid;
        }

        .attendance-checkbox span {
            border-color: #000;
        }

        .attendance-checkbox {
            width: 24px;
            height: 24px;
        }

        .attendance-checkbox span {
            width: 22px;
            height: 22px;
        }

        .attendance-presence-feedback,
        .attendance-presence-form noscript {
            display: none !important;
        }

        .attendance-sheet__note {
            color: #333;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php if ($shift !== null): ?>
    <?php $this->startSection('scripts'); ?>
    <script>
        document.querySelector('[data-print-report]')?.addEventListener(
            'click',
            () => window.print()
        );

        document.querySelectorAll('[data-attendance-presence-form]').forEach((form) => {
            const checkbox = form.querySelector('[data-attendance-presence-checkbox]');
            const feedback = form.querySelector('[data-attendance-presence-feedback]');

            if (!(checkbox instanceof HTMLInputElement)
                || !(feedback instanceof HTMLElement)) {
                return;
            }

            checkbox.addEventListener('change', async () => {
                const previousValue = !checkbox.checked;
                const formData = new FormData(form);

                checkbox.disabled = true;
                feedback.classList.remove('is-error');
                feedback.textContent = 'Opslaan…';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const result = await response.json();

                    if (!response.ok || result.success !== true) {
                        throw new Error(
                            result.message || 'De aanwezigheid kon niet worden opgeslagen.'
                        );
                    }

                    checkbox.checked = result.present === true;
                    feedback.textContent = 'Opgeslagen';
                } catch (error) {
                    checkbox.checked = previousValue;
                    feedback.classList.add('is-error');
                    feedback.textContent = error instanceof Error
                        ? error.message
                        : 'De aanwezigheid kon niet worden opgeslagen.';
                } finally {
                    checkbox.disabled = false;
                }
            });
        });
    </script>
    <?php $this->endSection(); ?>
<?php endif; ?>
