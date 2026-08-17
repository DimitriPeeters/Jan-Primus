<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var array<string, array<int, array<string, mixed>>> $options */
/** @var bool $mailConfigured */
/** @var array{active: bool, emails: string[]} $recipientRestriction */

$this->extend('layouts.app', ['title' => $title ?? 'Nieuwe mailing']);

$audienceType = (string) $helpers->old->get(
    'doelgroep_type',
    'alle_leden'
);
$selectedGroups = $helpers->old->get('groep_ids', []);
$selectedEvents = $helpers->old->get('event_ids', []);
$selectedShifts = $helpers->old->get('shift_ids', []);

$selectedGroups = is_array($selectedGroups)
    ? array_map('intval', $selectedGroups)
    : [];
$selectedEvents = is_array($selectedEvents)
    ? array_map('intval', $selectedEvents)
    : [];
$selectedShifts = is_array($selectedShifts)
    ? array_map('intval', $selectedShifts)
    : [];
?>

<?php $this->startSection('content'); ?>
<div class="mailing-compose-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Nieuwe mailing',
            'subtitle' => 'Schrijf één bericht; AEFS personaliseert en verstuurt elke mail afzonderlijk.',
            'actions' => sprintf(
                '<a class="btn btn-secondary" href="%s">Annuleren</a>',
                $this->escape($helpers->url->to('/mailings'))
            ),
        ]
    ) ?>

    <?php if ($recipientRestriction['active']): ?>
        <div class="alert alert-warning" role="status">
            <strong>Lokale mailtestmodus actief.</strong>
            Ongeacht de gekozen doelgroep worden uitsluitend
            <?= $this->escape(implode(', ', $recipientRestriction['emails'])) ?>
            als ontvanger opgenomen.
        </div>
    <?php endif; ?>

    <?php if (!$mailConfigured): ?>
        <div class="alert alert-warning" role="alert">
            SMTP is nog niet ingeschakeld. Je kunt deze mailing veilig voorbereiden en in de wachtrij plaatsen;
            er wordt pas echt verstuurd nadat de lokale mailconfiguratie actief is en de worker draait.
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?= $this->escape($helpers->url->to('/mailings/store')) ?>"
        enctype="multipart/form-data"
        class="mailing-compose-form"
    >
        <?= $helpers->csrf->field() ?>

        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Doelgroep</h2>
            </header>
            <div class="card__body mailing-form-grid">
                <div class="form-group mailing-full-width">
                    <label for="doelgroep_type">Versturen naar *</label>
                    <select
                        id="doelgroep_type"
                        name="doelgroep_type"
                        required
                        data-audience-type
                    >
                        <option value="alle_leden" <?= $audienceType === 'alle_leden' ? 'selected' : '' ?>>
                            Alle actieve leden
                        </option>
                        <option value="groep" <?= $audienceType === 'groep' ? 'selected' : '' ?>>
                            Leden van één of meer groepen
                        </option>
                        <option value="evenement" <?= $audienceType === 'evenement' ? 'selected' : '' ?>>
                            Actieve inschrijvingen van één of meer evenementen
                        </option>
                        <option value="shifts" <?= $audienceType === 'shifts' ? 'selected' : '' ?>>
                            Bevestigde en reserveleden van bepaalde shifts
                        </option>
                    </select>
                    <small>
                        Leden zonder geldig e-mailadres en leden op de mail-blacklist worden automatisch uitgesloten.
                    </small>
                </div>

                <div class="form-group mailing-full-width" data-audience-panel="groep" hidden>
                    <label for="groep_ids">Groepen *</label>
                    <select id="groep_ids" name="groep_ids[]" multiple size="6">
                        <?php foreach ($options['groups'] ?? [] as $group): ?>
                            <option
                                value="<?= (int) $group['id'] ?>"
                                <?= in_array((int) $group['id'], $selectedGroups, true) ? 'selected' : '' ?>
                            >
                                <?= $this->escape((string) $group['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Gebruik Ctrl (Windows) of Cmd (Mac) om meerdere groepen te kiezen.</small>
                </div>

                <div class="form-group mailing-full-width" data-audience-panel="evenement" hidden>
                    <label for="event_ids">Evenementen *</label>
                    <select id="event_ids" name="event_ids[]" multiple size="8">
                        <?php foreach ($options['events'] ?? [] as $event): ?>
                            <option
                                value="<?= (int) $event['id'] ?>"
                                <?= in_array((int) $event['id'], $selectedEvents, true) ? 'selected' : '' ?>
                            >
                                <?= $this->escape((string) $event['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Wachtende, bevestigde en reserve-inschrijvingen worden meegenomen; geweigerde en uitgeschreven leden niet.</small>
                </div>

                <div class="form-group mailing-full-width" data-audience-panel="shifts" hidden>
                    <label for="shift_ids">Shifts *</label>
                    <select id="shift_ids" name="shift_ids[]" multiple size="10">
                        <?php foreach ($options['shifts'] ?? [] as $shift): ?>
                            <option
                                value="<?= (int) $shift['id'] ?>"
                                <?= in_array((int) $shift['id'], $selectedShifts, true) ? 'selected' : '' ?>
                            >
                                <?= $this->escape((string) $shift['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Een lid dat in meerdere geselecteerde shifts staat, ontvangt de mailing slechts één keer.</small>
                </div>
            </div>
        </section>

        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Bericht</h2>
            </header>
            <div class="card__body mailing-form-grid">
                <div class="form-group mailing-full-width">
                    <label for="onderwerp">Onderwerp *</label>
                    <input
                        id="onderwerp"
                        name="onderwerp"
                        type="text"
                        maxlength="255"
                        value="<?= $this->escape(
                            (string) $helpers->old->get('onderwerp', '')
                        ) ?>"
                        required
                    >
                </div>

                <div class="form-group mailing-full-width">
                    <label for="inhoud">Inhoud *</label>
                    <textarea
                        id="inhoud"
                        name="inhoud"
                        rows="14"
                        maxlength="50000"
                        required
                    ><?= $this->escape(
                        (string) $helpers->old->get('inhoud', '')
                    ) ?></textarea>
                    <small>
                        AEFS voegt automatisch “Beste [voornaam]” en de vaste afsluiting toe. Lege regels vormen afzonderlijke alinea’s.
                    </small>
                </div>

                <div class="form-group mailing-full-width">
                    <label for="bijlage">Bijlage <span class="mailing-muted">(optioneel)</span></label>
                    <input
                        id="bijlage"
                        name="bijlage"
                        type="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.zip"
                    >
                    <small>Maximaal 10 MB. Toegestaan: PDF, Office, CSV, JPG, PNG en ZIP.</small>
                </div>
            </div>
        </section>

        <div class="mailing-compose-actions">
            <a class="btn btn-secondary" href="<?= $this->escape($helpers->url->to('/mailings')) ?>">
                Annuleren
            </a>
            <button class="btn btn-success" type="submit">
                Mailing inplannen
            </button>
        </div>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .mailing-compose-page,
    .mailing-compose-form {
        display: grid;
        gap: 1.25rem;
    }

    .mailing-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .mailing-full-width {
        grid-column: 1 / -1;
    }

    .mailing-compose-form select[multiple] {
        min-height: 150px;
        padding: 0.4rem;
    }

    .mailing-compose-form select[multiple] option {
        padding: 0.45rem 0.55rem;
    }

    .mailing-compose-form textarea {
        resize: vertical;
    }

    .mailing-compose-actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }

    .mailing-muted {
        color: var(--color-text-muted);
        font-weight: 400;
    }

    @media (max-width: 680px) {
        .mailing-form-grid {
            grid-template-columns: 1fr;
        }

        .mailing-compose-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .mailing-compose-actions .btn {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('scripts'); ?>
<script>
    (() => {
        const audience = document.querySelector('[data-audience-type]');
        const panels = Array.from(
            document.querySelectorAll('[data-audience-panel]')
        );

        if (!(audience instanceof HTMLSelectElement)) {
            return;
        }

        const updatePanels = () => {
            panels.forEach((panel) => {
                const active = panel.dataset.audiencePanel === audience.value;
                panel.hidden = !active;

                panel.querySelectorAll('select').forEach((select) => {
                    select.required = active;
                    select.disabled = !active;
                });
            });
        };

        audience.addEventListener('change', updatePanels);
        updatePanels();
    })();
</script>
<?php $this->endSection(); ?>
