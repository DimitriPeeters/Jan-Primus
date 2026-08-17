<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Event[] $events */
/** @var int $selectedEventId */
/** @var string|null $selectedGroupKey */
/** @var array<string, mixed>|null $report */
/** @var string|null $title */
/** @var string|null $applicationName */

$events ??= [];
$selectedEventId ??= 0;
$selectedGroupKey ??= null;
$report ??= null;

$event = $report['event'] ?? null;
$dates = $report['dates'] ?? [];
$sections = $report['sections'] ?? [];
$groupOptions = $report['group_options'] ?? [];
$selectedGroupLabel = $report['selected_group_label'] ?? null;
$money = static fn(int $cents): string => '€ ' . number_format(
    $cents / 100,
    2,
    ',',
    '.'
);

$actions = sprintf(
    '<a class="btn btn-secondary" href="%s">Terug naar rapporten</a>',
    $this->escape($helpers->url->to('/reports'))
);

if ($event instanceof Event) {
    $exportQuery = [
        'event_id' => $event->eventId,
    ];

    if ($selectedGroupKey !== null) {
        $exportQuery['groep'] = $selectedGroupKey;
    }

    $actions .= sprintf(
        ' <a class="btn btn-secondary" href="%s">Excel exporteren</a>',
        $this->escape(
            $helpers->url->to(
                '/reports/event-compensation/export?'
                . http_build_query($exportQuery)
            )
        )
    );
    $actions .= ' <button class="btn btn-primary" type="button" data-print-report>Afdrukken / PDF</button>';
}

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Vrijwilligersvergoedingen per evenement',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="compensation-report-page">
    <div class="report-screen-only">
        <?= $this->component(
            'page-header',
            [
                'title' => 'Vrijwilligersvergoedingen per evenement',
                'subtitle' => 'Alleen bevestigde en als aanwezig geregistreerde shiftmedewerkers worden opgenomen.',
                'actions' => $actions,
            ]
        ) ?>

        <section class="card compensation-filter-card">
            <div class="card__body">
                <?php if ($events === []): ?>
                    <?= $this->component(
                        'empty-state',
                        [
                            'title' => 'Geen evenementen beschikbaar',
                            'text' => 'Maak eerst een evenement aan voordat je dit rapport opent.',
                        ]
                    ) ?>
                <?php else: ?>
                    <form
                        method="get"
                        action="<?= $this->escape(
                            $helpers->url->to('/reports/event-compensation')
                        ) ?>"
                        class="compensation-filter-form"
                    >
                        <div class="form-group compensation-filter-field">
                            <label class="form-label" for="event_id">
                                Evenement
                            </label>
                            <select
                                class="form-control"
                                id="event_id"
                                name="event_id"
                                required
                            >
                                <option value="">Kies een evenement</option>
                                <?php foreach ($events as $availableEvent): ?>
                                    <option
                                        value="<?= $availableEvent->eventId ?>"
                                        <?= $availableEvent->eventId === $selectedEventId
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $this->escape(
                                            $availableEvent->titel
                                            . ' · '
                                            . $availableEvent->displayDate()
                                            . ($availableEvent->werktMetGroepen
                                                ? ' · groepen'
                                                : '')
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (
                            $event instanceof Event
                            && $event->werktMetGroepen
                            && $groupOptions !== []
                        ): ?>
                            <div class="form-group compensation-filter-field">
                                <label class="form-label" for="groep">
                                    Groep
                                </label>
                                <select
                                    class="form-control"
                                    id="groep"
                                    name="groep"
                                >
                                    <option value="">Alle groepen</option>
                                    <?php foreach ($groupOptions as $groupOption): ?>
                                        <option
                                            value="<?= $this->escape($groupOption['key']) ?>"
                                            <?= $groupOption['key'] === $selectedGroupKey
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= $this->escape($groupOption['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="compensation-filter-help">
                                    Selecteer één groep voor een afzonderlijke PDF of Excel-export.
                                </small>
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-primary" type="submit">
                            Rapport tonen
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if (!$event instanceof Event): ?>
        <?php if ($events !== []): ?>
            <div class="report-screen-only">
                <?= $this->component(
                    'empty-state',
                    [
                        'title' => 'Selecteer een evenement',
                        'text' => 'Kies hierboven het evenement waarvoor je de vergoedingen wilt berekenen.',
                    ]
                ) ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <section class="compensation-sheet" aria-labelledby="compensation-report-title">
            <header class="compensation-sheet__header">
                <div>
                    <span class="compensation-sheet__brand">
                        <?= $this->escape($applicationName ?? 'AEFS Eventbeheer') ?>
                    </span>
                    <h1 id="compensation-report-title">Vrijwilligersvergoedingen</h1>
                    <p>
                        <?= $this->escape($event->titel) ?>
                        <?= $selectedGroupLabel !== null
                            ? ' · ' . $this->escape($selectedGroupLabel)
                            : '' ?>
                    </p>
                </div>

                <span class="badge <?= $event->werktMetGroepen
                    ? 'badge-info'
                    : 'badge-success' ?> report-screen-only">
                    <?= $event->werktMetGroepen
                        ? 'Groepsvergoedingen actief'
                        : 'Individuele vergoedingen' ?>
                </span>
            </header>

            <dl class="compensation-meta">
                <div>
                    <dt>Periode</dt>
                    <dd><?= $this->escape($event->displayDate()) ?></dd>
                </div>
                <div>
                    <dt>Gewerkte shifts</dt>
                    <dd><?= (int) ($report['shift_count'] ?? 0) ?></dd>
                </div>
                <div>
                    <dt>Groepstoeslag</dt>
                    <dd><?= $event->werktMetGroepen
                        ? $this->escape(
                            $money((int) ($report['group_supplement_cents'] ?? 0))
                        ) . ' per shift'
                        : 'Niet van toepassing' ?></dd>
                </div>
                <div>
                    <dt><?= $selectedGroupLabel !== null
                        ? 'Totaal groep'
                        : 'Algemeen totaal' ?></dt>
                    <dd><?= $this->escape(
                        $money((int) ($report['total_cents'] ?? 0))
                    ) ?></dd>
                </div>
            </dl>

            <?php if ($sections === []): ?>
                <div class="compensation-empty">
                    Voor dit evenement zijn nog geen bevestigde vrijwilligers als aanwezig geregistreerd.
                </div>
            <?php else: ?>
                <?php foreach ($sections as $section): ?>
                    <?php
                    $isGroup = (bool) ($section['is_group'] ?? false);
                    $sectionLabel = (string) ($section['label'] ?? 'Vrijwilligers');
                    $minimumTableWidth = max(
                        760,
                        430 + (count($dates) * 112)
                    );
                    ?>
                    <article class="compensation-section">
                        <header class="compensation-section__header">
                            <div>
                                <span><?= $isGroup ? 'Vereniging' : 'Uitbetaling' ?></span>
                                <h2><?= $this->escape($sectionLabel) ?></h2>
                                <p>
                                    <?= $isGroup
                                        ? 'Totaal uit te betalen aan deze vereniging.'
                                        : ($event->werktMetGroepen
                                            ? 'Individuele vergoeding voor medewerkers zonder groep.'
                                            : 'Individuele vrijwilligersvergoedingen.') ?>
                                </p>
                            </div>

                            <div class="compensation-section__total">
                                <span><?= (int) ($section['shift_count'] ?? 0) ?> shift(s)</span>
                                <strong><?= $this->escape(
                                    $money((int) ($section['total_cents'] ?? 0))
                                ) ?></strong>
                            </div>
                        </header>

                        <div class="table-responsive compensation-table-wrapper">
                            <table
                                class="table compensation-table"
                                style="min-width: <?= $minimumTableWidth ?>px"
                            >
                                <thead>
                                    <tr>
                                        <th scope="col" class="compensation-name compensation-name--last">
                                            Naam
                                        </th>
                                        <th scope="col" class="compensation-name compensation-name--first">
                                            Voornaam
                                        </th>
                                        <?php foreach ($dates as $date): ?>
                                            <th scope="col" class="compensation-day">
                                                <?= $this->escape(
                                                    BelgianDateTime::formatDate($date)
                                                ) ?>
                                            </th>
                                        <?php endforeach; ?>
                                        <th scope="col" class="compensation-total-column">
                                            Totaal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($section['members'] as $member): ?>
                                        <tr>
                                            <th scope="row" class="compensation-name compensation-name--last">
                                                <?= $this->escape($member['last_name']) ?>
                                            </th>
                                            <td class="compensation-name compensation-name--first">
                                                <?= $this->escape($member['first_name']) ?>
                                            </td>
                                            <?php foreach ($dates as $date): ?>
                                                <?php $day = $member['days'][$date]; ?>
                                                <td class="compensation-amount">
                                                    <?php if ($day['shift_count'] > 0): ?>
                                                        <strong><?= $this->escape(
                                                            $money($day['amount_cents'])
                                                        ) ?></strong>
                                                        <?php if ($day['shift_count'] > 1): ?>
                                                            <small><?= $day['shift_count'] ?> shifts</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span aria-label="Geen gewerkte shift">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="compensation-total-column">
                                                <strong><?= $this->escape(
                                                    $money($member['total_cents'])
                                                ) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" scope="row">Totaal <?= $this->escape($sectionLabel) ?></th>
                                        <?php foreach ($dates as $date): ?>
                                            <td class="compensation-amount">
                                                <?= $this->escape(
                                                    $money($section['day_totals'][$date])
                                                ) ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="compensation-total-column">
                                            <?= $this->escape(
                                                $money($section['total_cents'])
                                            ) ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

            <p class="compensation-sheet__note">
                Dit rapport is berekend op basis van bevestigde shiftinschrijvingen die als aanwezig zijn gemarkeerd.
                Bedragen volgen de actuele shiftvergoeding en groepsindeling in AEFS.
            </p>
        </section>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .compensation-report-page {
        display: grid;
        gap: 1.25rem;
    }

    .compensation-filter-form {
        display: flex;
        align-items: flex-end;
        gap: 1rem;
    }

    .compensation-filter-field {
        flex: 1;
        margin: 0;
    }

    .compensation-filter-help {
        display: block;
        margin-top: 0.35rem;
        color: var(--color-text-muted);
        line-height: 1.4;
    }

    .compensation-sheet {
        padding: 1.5rem;
        background: #fff;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-large);
        box-shadow: var(--shadow-card);
    }

    .compensation-sheet__header,
    .compensation-section__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .compensation-sheet__header {
        padding-bottom: 1.25rem;
        border-bottom: 2px solid var(--color-primary);
    }

    .compensation-sheet__brand,
    .compensation-section__header > div:first-child > span {
        display: block;
        margin-bottom: 0.3rem;
        color: var(--color-primary);
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.055em;
        text-transform: uppercase;
    }

    .compensation-sheet__header h1,
    .compensation-sheet__header p,
    .compensation-section__header h2,
    .compensation-section__header p {
        margin: 0;
    }

    .compensation-sheet__header h1 {
        font-size: 1.65rem;
    }

    .compensation-sheet__header p,
    .compensation-section__header p {
        margin-top: 0.3rem;
        color: var(--color-text-muted);
    }

    .compensation-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin: 1.25rem 0;
    }

    .compensation-meta dt {
        margin-bottom: 0.25rem;
        color: var(--color-text-muted);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
    }

    .compensation-meta dd {
        margin: 0;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .compensation-section {
        margin-top: 1.5rem;
        break-inside: avoid-page;
    }

    .compensation-section__header {
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .compensation-section__total {
        display: grid;
        gap: 0.15rem;
        flex: 0 0 auto;
        text-align: right;
    }

    .compensation-section__total span {
        color: var(--color-text-muted);
        font-size: 0.8rem;
    }

    .compensation-section__total strong {
        color: var(--color-primary);
        font-size: 1.15rem;
    }

    .compensation-table-wrapper {
        max-width: 100%;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
        -webkit-overflow-scrolling: touch;
    }

    .compensation-table {
        width: 100%;
        table-layout: fixed;
    }

    .compensation-table th,
    .compensation-table td {
        padding: 0.75rem 0.65rem;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .compensation-name {
        position: sticky;
        z-index: 2;
        width: 150px;
        overflow-wrap: anywhere;
        background: #fff;
    }

    .compensation-name--last {
        left: 0;
    }

    .compensation-name--first {
        left: 150px;
        box-shadow: 8px 0 12px -12px rgb(15 23 42 / 70%);
    }

    thead .compensation-name {
        z-index: 4;
        background: #f8fafc;
    }

    .compensation-day {
        width: 112px;
        text-align: center !important;
    }

    .compensation-amount,
    .compensation-total-column {
        text-align: right !important;
        white-space: nowrap;
    }

    .compensation-amount strong,
    .compensation-amount small {
        display: block;
    }

    .compensation-amount small {
        margin-top: 0.1rem;
        color: var(--color-text-muted);
        font-size: 0.7rem;
    }

    .compensation-total-column {
        position: sticky;
        right: 0;
        z-index: 2;
        width: 130px;
        background: #fffdf5;
        box-shadow: -8px 0 12px -12px rgb(15 23 42 / 70%);
    }

    thead .compensation-total-column {
        z-index: 4;
        background: #fff7d6;
    }

    .compensation-table tfoot th,
    .compensation-table tfoot td {
        font-weight: 800;
        background: #f8fafc;
        border-top: 2px solid var(--color-border-strong);
    }

    .compensation-table tfoot .compensation-total-column {
        background: #fff7d6;
    }

    .compensation-empty {
        padding: 2rem;
        color: var(--color-text-muted);
        text-align: center;
        background: #f8fafc;
        border: 1px dashed var(--color-border-strong);
        border-radius: var(--radius-medium);
    }

    .compensation-sheet__note {
        margin: 1rem 0 0;
        color: var(--color-text-muted);
        font-size: 0.8rem;
    }

    @media (max-width: 1100px) {
        .compensation-sheet {
            padding: 1.1rem;
        }

        .compensation-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .compensation-name {
            width: 135px;
        }

        .compensation-name--first {
            left: 135px;
        }
    }

    @media (max-width: 700px) {
        .compensation-filter-form,
        .compensation-section__header {
            align-items: stretch;
            flex-direction: column;
        }

        .compensation-filter-form .btn {
            min-height: 44px;
        }

        .compensation-meta {
            grid-template-columns: 1fr;
        }

        .compensation-section__total {
            text-align: left;
        }

        .compensation-name {
            width: 120px;
        }

        .compensation-name--first {
            left: 120px;
        }
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
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

        .compensation-sheet {
            padding: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .compensation-table-wrapper {
            overflow: visible;
            border: 0;
        }

        .compensation-table {
            min-width: 0 !important;
        }

        .compensation-table th,
        .compensation-table td {
            position: static;
            width: auto;
            padding: 5px 6px;
            color: #000;
            font-size: 0.72rem;
            background: #fff;
            border: 1px solid #000;
            box-shadow: none;
        }

        .compensation-table tr {
            break-inside: avoid;
        }

        .compensation-section {
            break-inside: auto;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php if ($event instanceof Event): ?>
    <?php $this->startSection('scripts'); ?>
    <script>
        document.querySelector('[data-print-report]')?.addEventListener(
            'click',
            () => window.print()
        );
    </script>
    <?php $this->endSection(); ?>
<?php endif; ?>
