<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\EventRegistration;

/** @var ViewHelpers $helpers */
/** @var Event[] $events */
/** @var int $selectedEventId */
/** @var Event|null $event */
/** @var EventRegistration[] $registrations */
/** @var string|null $title */
/** @var string|null $applicationName */

$events ??= [];
$selectedEventId ??= 0;
$event ??= null;
$registrations ??= [];
$actions = sprintf('<a class="btn btn-secondary" href="%s">Terug naar rapporten</a>', $this->escape($helpers->url->to('/reports')));

if ($event !== null) {
    $actions .= ' <button class="btn btn-primary" type="button" data-print-report>Afdrukken</button>';
}

$this->extend('layouts.app', ['title' => $title ?? 'Ingeschreven leden per evenement']);
?>

<?php $this->startSection('content'); ?>
<div class="event-participants-page">
    <div class="report-screen-only">
        <?= $this->component('page-header', [
            'title' => 'Ingeschreven leden per evenement',
            'subtitle' => 'Ieder actueel ingeschreven lid staat precies één keer en alfabetisch op achternaam in de lijst.',
            'actions' => $actions,
        ]) ?>

        <section class="card report-filter-card"><div class="card__body">
            <?php if ($events === []): ?>
                <?= $this->component('empty-state', ['title' => 'Geen evenementen beschikbaar', 'text' => 'Maak eerst een evenement aan.']) ?>
            <?php else: ?>
                <form method="get" action="<?= $this->escape($helpers->url->to('/reports/event-participants')) ?>" class="report-filter-form">
                    <div class="form-group report-filter-field">
                        <label class="form-label" for="event_id">Evenement</label>
                        <select class="form-control" id="event_id" name="event_id" required>
                            <option value="">Kies een evenement</option>
                            <?php foreach ($events as $availableEvent): ?>
                                <option value="<?= $availableEvent->eventId ?>" <?= $availableEvent->eventId === $selectedEventId ? 'selected' : '' ?>>
                                    <?= $this->escape(sprintf('%s - %s - %d ingeschreven', $availableEvent->titel, $availableEvent->displayDate(), $availableEvent->aantalInschrijvingen)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Lijst tonen</button>
                </form>
            <?php endif; ?>
        </div></section>
    </div>

    <?php if ($event === null): ?>
        <?php if ($events !== []): ?><div class="report-screen-only"><?= $this->component('empty-state', ['title' => 'Selecteer een evenement', 'text' => 'Kies hierboven het evenement waarvan je de ledenlijst wilt maken.']) ?></div><?php endif; ?>
    <?php else: ?>
        <section class="participants-sheet" aria-labelledby="participants-title">
            <header class="participants-sheet__header">
                <div>
                    <span class="participants-sheet__brand"><?= $this->escape($applicationName ?? 'Ledenbeheer') ?></span>
                    <h1 id="participants-title">Ingeschreven leden</h1>
                    <p><?= $this->escape($event->titel) ?></p>
                </div>
            </header>

            <dl class="participants-meta">
                <div><dt>Evenement</dt><dd><?= $this->escape($event->titel) ?></dd></div>
                <div><dt>Periode</dt><dd><?= $this->escape($event->displayDate()) ?></dd></div>
                <div><dt>Unieke leden</dt><dd><?= count($registrations) ?></dd></div>
            </dl>

            <?php if ($registrations === []): ?>
                <div class="participants-empty">Voor dit evenement zijn er geen actuele inschrijvingen.</div>
            <?php else: ?>
                <div class="table-responsive participants-table-wrapper">
                    <table class="table participants-table">
                        <thead><tr><th>Naam</th><th>Voornaam</th><th>E-mailadres</th><th>Gekozen dag(en)</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><?= $this->escape($registration->lidAchternaam ?? '—') ?></td>
                                <td><?= $this->escape($registration->lidVoornaam ?? '—') ?></td>
                                <td><?= $this->escape($registration->lidEmail ?? '—') ?></td>
                                <td><?= $this->escape($registration->displayDagen()) ?></td>
                                <td><?= $this->escape($registration->statusLabel()) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p class="participants-sheet__note">Uitgeschreven en geweigerde inschrijvingen worden niet in deze actuele lijst opgenomen.</p>
        </section>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .event-participants-page{display:grid;gap:1.25rem}.report-filter-form{display:flex;align-items:flex-end;gap:1rem}.report-filter-field{flex:1;margin:0}.participants-sheet{padding:1.75rem;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-large);box-shadow:var(--shadow-card)}.participants-sheet__header{padding-bottom:1.25rem;border-bottom:2px solid var(--color-primary)}.participants-sheet__brand{display:block;margin-bottom:.35rem;color:var(--color-primary);font-size:.78rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.participants-sheet__header h1,.participants-sheet__header p{margin:0}.participants-sheet__header p{margin-top:.35rem;color:var(--color-text-muted)}.participants-meta{display:grid;grid-template-columns:2fr 1fr 1fr;gap:1rem;margin:1.25rem 0}.participants-meta dt{margin-bottom:.25rem;color:var(--color-text-muted);font-size:.72rem;font-weight:700;letter-spacing:.045em;text-transform:uppercase}.participants-meta dd{margin:0;font-weight:700}.participants-table-wrapper{border:1px solid var(--color-border);border-radius:var(--radius-medium)}.participants-table{width:100%;table-layout:fixed}.participants-table th:nth-child(1),.participants-table th:nth-child(2){width:15%}.participants-table th:nth-child(3){width:24%}.participants-table th:nth-child(5){width:13%}.participants-empty{padding:2rem;color:var(--color-text-muted);text-align:center;background:#f8fafc;border:1px dashed var(--color-border-strong);border-radius:var(--radius-medium)}.participants-sheet__note{margin:1rem 0 0;color:var(--color-text-muted);font-size:.8rem}@media(max-width:700px){.report-filter-form{align-items:stretch;flex-direction:column}.participants-sheet{padding:1rem}.participants-meta{grid-template-columns:1fr}.participants-table{min-width:800px}}@media print{@page{size:A4 landscape;margin:12mm}body{color:#000;background:#fff}.sidebar,.app-header,.footer,.report-screen-only{display:none!important}.app,.app__main{display:block;min-height:0;margin:0}.app__content{padding:0}.participants-sheet{padding:0;border:0;border-radius:0;box-shadow:none}.participants-table-wrapper{overflow:visible;border:0}.participants-table th,.participants-table td{padding:6px 8px;color:#000;border:1px solid #000}.participants-table tr{break-inside:avoid}}
</style>
<?php $this->endSection(); ?>

<?php if ($event !== null): ?><?php $this->startSection('scripts'); ?><script>document.querySelector('[data-print-report]')?.addEventListener('click',()=>window.print());</script><?php $this->endSection(); ?><?php endif; ?>
