<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var string|null $title */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Rapporten',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="reports-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Rapporten',
            'subtitle' => 'Maak operationele lijsten op basis van de actuele gegevens in Ledenbeheer.',
        ]
    ) ?>

    <div class="report-grid">
        <article class="card report-card">
            <header class="card__header">
                <div>
                    <span class="report-card__type">Shiften</span>
                    <h2 class="card__title">Aanwezigheidslijst per shift</h2>
                </div>
            </header>

            <div class="card__body report-card__body">
                <p>
                    Selecteer een shift en maak een afdrukbare lijst met naam,
                    voornaam en een aanwezigheidsvakje voor iedere bevestigde vrijwilliger.
                </p>

                <a
                    class="btn btn-primary"
                    href="<?= $this->escape(
                        $helpers->url->to('/reports/shift-attendance')
                    ) ?>"
                >
                    Rapport openen
                </a>
            </div>
        </article>

        <article class="card report-card">
            <header class="card__header">
                <div>
                    <span class="report-card__type">Eventdagen</span>
                    <h2 class="card__title">Aanwezigheidslijst per dag</h2>
                </div>
            </header>

            <div class="card__body report-card__body">
                <p>
                    Selecteer een datum en bekijk alle bevestigde medewerkers
                    alfabetisch op achternaam, met hun shifts en aanwezigheidsvakjes.
                </p>

                <a
                    class="btn btn-primary"
                    href="<?= $this->escape(
                        $helpers->url->to('/reports/day-attendance')
                    ) ?>"
                >
                    Rapport openen
                </a>
            </div>
        </article>

        <article class="card report-card">
            <header class="card__header">
                <div>
                    <span class="report-card__type">Evenementen</span>
                    <h2 class="card__title">Ingeschreven leden per evenement</h2>
                </div>
            </header>

            <div class="card__body report-card__body">
                <p>
                    Maak een afdrukbare, alfabetische lijst van alle unieke leden
                    met een actuele inschrijving voor het geselecteerde evenement.
                </p>

                <a
                    class="btn btn-primary"
                    href="<?= $this->escape(
                        $helpers->url->to('/reports/event-participants')
                    ) ?>"
                >
                    Rapport openen
                </a>
            </div>
        </article>

    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .reports-page {
        display: grid;
        gap: 1.25rem;
    }

    .report-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .report-card {
        min-height: 240px;
    }

    .report-card__type {
        display: block;
        margin-bottom: 0.35rem;
        color: var(--color-primary);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
    }

    .report-card__body {
        display: flex;
        height: calc(100% - 78px);
        align-items: flex-start;
        flex-direction: column;
        gap: 1rem;
    }

    .report-card__body p {
        flex: 1;
        margin: 0;
        color: var(--color-text-muted);
        line-height: 1.6;
    }

    .report-card__body .btn {
        min-height: 44px;
    }

    @media (max-width: 1100px) {
        .report-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 820px) {
        .report-grid {
            grid-template-columns: 1fr;
        }

        .report-card {
            min-height: 0;
        }

        .report-card__body {
            height: auto;
        }
    }
</style>
<?php $this->endSection(); ?>
