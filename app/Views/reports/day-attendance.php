<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Shift;
use App\Models\ShiftRegistration;

/** @var ViewHelpers $helpers */
/** @var array<int, array{date: string, label: string, assignments: int}> $days */
/** @var string $selectedDate */
/** @var array<string, mixed>|null $report */
/** @var string|null $title */
/** @var string|null $applicationName */

$days ??= [];
$selectedDate ??= '';
$report ??= null;

$actions = sprintf(
    '<a class="btn btn-secondary" href="%s">Terug naar rapporten</a>',
    $this->escape($helpers->url->to('/reports'))
);

if ($report !== null) {
    $actions .= ' <button class="btn btn-primary" type="button" data-print-report>Afdrukken</button>';
}

$this->extend('layouts.app', ['title' => $title ?? 'Aanwezigheidslijst per dag']);
?>

<?php $this->startSection('content'); ?>
<div class="day-attendance-page">
    <div class="report-screen-only">
        <?= $this->component('page-header', [
            'title' => 'Aanwezigheidslijst per dag',
            'subtitle' => 'Ieder lid staat alfabetisch één keer in de lijst; aanwezigheid blijft per shift geregistreerd.',
            'actions' => $actions,
        ]) ?>

        <section class="card report-filter-card">
            <div class="card__body">
                <?php if ($days === []): ?>
                    <?= $this->component('empty-state', [
                        'title' => 'Geen eventdagen beschikbaar',
                        'text' => 'Maak eerst shifts aan voordat je een dagelijkse aanwezigheidslijst opent.',
                    ]) ?>
                <?php else: ?>
                    <form method="get" action="<?= $this->escape($helpers->url->to('/reports/day-attendance')) ?>" class="report-filter-form">
                        <div class="form-group report-filter-field">
                            <label class="form-label" for="date">Datum</label>
                            <select class="form-control" id="date" name="date" required>
                                <option value="">Kies een datum</option>
                                <?php foreach ($days as $day): ?>
                                    <option value="<?= $this->escape($day['date']) ?>" <?= $day['date'] === $selectedDate ? 'selected' : '' ?>>
                                        <?= $this->escape(sprintf('%s - %d bevestigde shiftinschrijvingen', $day['label'], $day['assignments'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Lijst tonen</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if ($report === null): ?>
        <?php if ($days !== []): ?>
            <div class="report-screen-only">
                <?= $this->component('empty-state', ['title' => 'Selecteer een datum', 'text' => 'Kies hierboven de dag waarvoor je de aanwezigheidslijst wilt maken.']) ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <section class="attendance-sheet" aria-labelledby="day-attendance-title">
            <header class="attendance-sheet__header">
                <div>
                    <span class="attendance-sheet__brand"><?= $this->escape($applicationName ?? 'Ledenbeheer') ?></span>
                    <h1 id="day-attendance-title">Aanwezigheidslijst per dag</h1>
                    <p><?= $this->escape($report['displayDate']) ?></p>
                </div>
            </header>

            <dl class="attendance-meta">
                <div><dt>Datum</dt><dd><?= $this->escape($report['displayDate']) ?></dd></div>
                <div><dt>Medewerkers</dt><dd><?= count($report['people']) ?></dd></div>
                <div><dt>Shiftinschrijvingen</dt><dd><?= (int) $report['assignmentCount'] ?></dd></div>
            </dl>

            <?php if ($report['people'] === []): ?>
                <div class="attendance-empty">Voor deze dag zijn er geen bevestigde medewerkers.</div>
            <?php else: ?>
                <div class="table-responsive attendance-table-wrapper">
                    <table class="table attendance-table">
                        <thead><tr><th>Naam</th><th>Voornaam</th><th>Shift(s) en aanwezigheid</th><th class="walkie-column">Nummer walkie</th><th class="earpiece-column">Oortje</th></tr></thead>
                        <tbody>
                        <?php foreach ($report['people'] as $person): ?>
                            <tr>
                                <td><?= $this->escape($person['lastName'] !== '' ? $person['lastName'] : '—') ?></td>
                                <td><?= $this->escape($person['firstName'] !== '' ? $person['firstName'] : '—') ?></td>
                                <td>
                                    <div class="day-assignments">
                                    <?php foreach ($person['assignments'] as $assignment): ?>
                                        <?php
                                        /** @var ShiftRegistration $registration */
                                        $registration = $assignment['registration'];
                                        /** @var Shift $shift */
                                        $shift = $assignment['shift'];
                                        $label = sprintf('%s - %s - %s', $shift->displayTijdvak(), $shift->displayNaam(), $shift->eventTitel ?? 'Evenement');
                                        ?>
                                        <form method="post" action="<?= $this->escape($helpers->url->to('/shift-registrations/' . $registration->inschrijvingId . '/presence')) ?>" class="attendance-presence-form" data-attendance-presence-form>
                                            <?= $helpers->csrf->field() ?>
                                            <label class="day-assignment-label">
                                                <span class="attendance-checkbox">
                                                    <input type="checkbox" name="aanwezig" value="1" aria-label="<?= $this->escape('Aanwezig: ' . $person['firstName'] . ' ' . $person['lastName'] . ', ' . $label) ?>" data-attendance-presence-checkbox <?= $registration->aanwezig ? 'checked' : '' ?>>
                                                    <span aria-hidden="true"></span>
                                                </span>
                                                <span><?= $this->escape($label) ?></span>
                                            </label>
                                            <noscript><button class="btn btn-sm btn-secondary" type="submit">Opslaan</button></noscript>
                                            <span class="attendance-presence-feedback" role="status" aria-live="polite" data-attendance-presence-feedback></span>
                                        </form>
                                    <?php endforeach; ?>
                                    </div>
                                </td>
                                <?php $detailsFormId = 'day-details-' . (int) $person['memberId']; ?>
                                <td class="walkie-column">
                                    <form
                                        id="<?= $detailsFormId ?>"
                                        method="post"
                                        action="<?= $this->escape($helpers->url->to('/reports/day-attendance/details')) ?>"
                                        class="day-details-form"
                                        data-day-details-form
                                    >
                                        <?= $helpers->csrf->field() ?>
                                        <input type="hidden" name="date" value="<?= $this->escape($report['date']) ?>">
                                        <input type="hidden" name="member_id" value="<?= (int) $person['memberId'] ?>">
                                        <input type="hidden" name="oortje" value="0">
                                        <input
                                            class="form-control walkie-input"
                                            type="text"
                                            name="nummer_walkie"
                                            value="<?= $this->escape($person['walkieNumber']) ?>"
                                            maxlength="10"
                                            aria-label="<?= $this->escape('Nummer walkie voor ' . $person['firstName'] . ' ' . $person['lastName']) ?>"
                                            data-day-details-input
                                        >
                                        <noscript><button class="btn btn-sm btn-secondary" type="submit">Opslaan</button></noscript>
                                        <span class="attendance-presence-feedback" role="status" aria-live="polite" data-day-details-feedback></span>
                                    </form>
                                </td>
                                <td class="earpiece-column">
                                    <label class="attendance-checkbox">
                                        <input
                                            type="checkbox"
                                            name="oortje"
                                            value="1"
                                            form="<?= $detailsFormId ?>"
                                            aria-label="<?= $this->escape('Oortje voor ' . $person['firstName'] . ' ' . $person['lastName']) ?>"
                                            data-day-details-input
                                            <?= $person['earpiece'] ? 'checked' : '' ?>
                                        >
                                        <span aria-hidden="true"></span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p class="attendance-sheet__note">Wijzigingen worden onmiddellijk per shift in Ledenbeheer opgeslagen.</p>
        </section>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .day-attendance-page { display:grid; gap:1.25rem; }
    .report-filter-form { display:flex; align-items:flex-end; gap:1rem; }
    .report-filter-field { flex:1; margin:0; }
    .attendance-sheet { padding:1.75rem; background:#fff; border:1px solid var(--color-border); border-radius:var(--radius-large); box-shadow:var(--shadow-card); }
    .attendance-sheet__header { padding-bottom:1.25rem; border-bottom:2px solid var(--color-primary); }
    .attendance-sheet__brand { display:block; margin-bottom:.35rem; color:var(--color-primary); font-size:.78rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .attendance-sheet__header h1,.attendance-sheet__header p { margin:0; }
    .attendance-sheet__header p { margin-top:.35rem; color:var(--color-text-muted); }
    .attendance-meta { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin:1.25rem 0; }
    .attendance-meta dt { margin-bottom:.25rem; color:var(--color-text-muted); font-size:.72rem; font-weight:700; letter-spacing:.045em; text-transform:uppercase; }
    .attendance-meta dd { margin:0; font-weight:700; }
    .attendance-table-wrapper { border:1px solid var(--color-border); border-radius:var(--radius-medium); }
    .attendance-table { width:100%; table-layout:fixed; }
    .attendance-table th:nth-child(1),.attendance-table th:nth-child(2) { width:14%; }
    .walkie-column { width:130px; }
    .earpiece-column { width:80px; text-align:center!important; }
    .walkie-input { width:100%; max-width:10ch; box-sizing:border-box; }
    .day-details-form { display:grid; justify-items:start; gap:.2rem; margin:0; }
    .day-assignments { display:grid; gap:.5rem; }
    .attendance-presence-form { display:flex; align-items:center; gap:.5rem; margin:0; }
    .day-assignment-label { display:flex; flex:1; align-items:center; gap:.6rem; cursor:pointer; }
    .attendance-checkbox { position:relative; display:inline-flex; width:36px; height:36px; flex:0 0 36px; align-items:center; justify-content:center; }
    .attendance-checkbox input { position:absolute; width:1px; height:1px; opacity:0; }
    .attendance-checkbox span { display:flex; width:25px; height:25px; align-items:center; justify-content:center; background:#fff; border:2px solid #64748b; border-radius:4px; }
    .attendance-checkbox input:focus-visible + span { outline:3px solid rgb(239 96 18 / 25%); outline-offset:2px; }
    .attendance-checkbox input:disabled + span { opacity:.55; }
    .attendance-checkbox input:checked + span::after { color:#166534; content:'✓'; font-weight:900; }
    .attendance-presence-feedback { color:#166534; font-size:.72rem; font-weight:700; }
    .attendance-presence-feedback.is-error { color:#b91c1c; }
    .attendance-empty { padding:2rem; color:var(--color-text-muted); text-align:center; background:#f8fafc; border:1px dashed var(--color-border-strong); border-radius:var(--radius-medium); }
    .attendance-sheet__note { margin:1rem 0 0; color:var(--color-text-muted); font-size:.8rem; }
    @media(max-width:700px){.report-filter-form{align-items:stretch;flex-direction:column}.attendance-sheet{padding:1rem}.attendance-meta{grid-template-columns:1fr}.attendance-table{min-width:850px}.attendance-table th,.attendance-table td{padding:.65rem .45rem;font-size:.88rem}}
    @media print{@page{size:A4 portrait;margin:12mm}body{color:#000;background:#fff}.sidebar,.app-header,.footer,.report-screen-only{display:none!important}.app,.app__main{display:block;min-height:0;margin:0}.app__content{padding:0}.attendance-sheet{padding:0;border:0;border-radius:0;box-shadow:none}.attendance-table-wrapper{overflow:visible;border:0}.attendance-table th,.attendance-table td{padding:6px 8px;color:#000;border:1px solid #000}.attendance-table tr{break-inside:avoid}.attendance-presence-feedback,.attendance-presence-form noscript{display:none!important}}
</style>
<?php $this->endSection(); ?>

<?php if ($report !== null): ?>
<?php $this->startSection('scripts'); ?>
<script>
document.querySelector('[data-print-report]')?.addEventListener('click',()=>window.print());
document.querySelectorAll('[data-attendance-presence-form]').forEach((form)=>{
    const checkbox=form.querySelector('[data-attendance-presence-checkbox]');
    const feedback=form.querySelector('[data-attendance-presence-feedback]');
    if(!(checkbox instanceof HTMLInputElement)||!(feedback instanceof HTMLElement))return;
    checkbox.addEventListener('change',async()=>{
        const previous=!checkbox.checked;
        checkbox.disabled=true; feedback.classList.remove('is-error'); feedback.textContent='Opslaan…';
        try{
            const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            const result=await response.json();
            if(!response.ok||result.success!==true)throw new Error(result.message||'De aanwezigheid kon niet worden opgeslagen.');
            checkbox.checked=result.present===true; feedback.textContent='Opgeslagen';
        }catch(error){checkbox.checked=previous;feedback.classList.add('is-error');feedback.textContent=error instanceof Error?error.message:'De aanwezigheid kon niet worden opgeslagen.';}
        finally{checkbox.disabled=false;}
    });
});

document.querySelectorAll('[data-day-details-form]').forEach((form)=>{
    const feedback=form.querySelector('[data-day-details-feedback]');
    const inputs=document.querySelectorAll(`[data-day-details-input][form="${form.id}"], #${form.id} [data-day-details-input]`);
    if(!(feedback instanceof HTMLElement))return;
    let timer;
    const save=async()=>{
        clearTimeout(timer); feedback.classList.remove('is-error'); feedback.textContent='Opslaan…';
        const formData=new FormData(form);
        inputs.forEach((input)=>input.disabled=true);
        try{
            const response=await fetch(form.action,{method:'POST',body:formData,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            const result=await response.json();
            if(!response.ok||result.success!==true)throw new Error(result.message||'De daggegevens konden niet worden opgeslagen.');
            feedback.textContent='Opgeslagen';
        }catch(error){feedback.classList.add('is-error');feedback.textContent=error instanceof Error?error.message:'De daggegevens konden niet worden opgeslagen.';}
        finally{inputs.forEach((input)=>input.disabled=false);}
    };
    inputs.forEach((input)=>{
        input.addEventListener(input instanceof HTMLInputElement&&input.type==='text'?'input':'change',()=>{
            clearTimeout(timer); timer=setTimeout(save,input instanceof HTMLInputElement&&input.type==='text'?500:0);
        });
    });
});
</script>
<?php $this->endSection(); ?>
<?php endif; ?>
