<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\Shift;
use App\Models\ShiftType;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Event|null $event */
/** @var ShiftType[] $shiftTypes */
/** @var Shift[] $shifts */

$event ??= null;
$shiftTypes ??= [];
$shifts ??= [];

$oldInput = $helpers->old->all();

$value = static function (
    string $key,
    mixed $default = ''
) use ($oldInput): mixed {
    return array_key_exists($key, $oldInput)
        ? $oldInput[$key]
        : $default;
};

$titel = (string) $value(
    'titel',
    $event?->titel ?? ''
);

$beschrijving = (string) $value(
    'beschrijving',
    $event?->beschrijving ?? ''
);

$locatie = (string) $value(
    'locatie',
    $event?->locatie ?? ''
);

$maxDeelnemers = $value(
    'max_deelnemers',
    $event?->maxDeelnemers ?? ''
);

$startdatum = (string) $value(
    'startdatum',
    BelgianDateTime::formatDate(
        $event?->startDatum,
        ''
    )
);

$einddatum = (string) $value(
    'einddatum',
    BelgianDateTime::formatDate(
        $event?->eindDatum,
        ''
    )
);

$status = (string) $value(
    'status',
    $event?->status ?? Event::STATUS_CONCEPT
);

$oldShiftRows = $oldInput['shifts'] ?? [];
$oldShiftRows = is_array($oldShiftRows)
    ? array_values(
        array_filter(
            $oldShiftRows,
            static fn(mixed $row): bool => is_array($row)
        )
    )
    : [];
?>

<section class="card event-form-card">
    <header class="card__header">
        <h2 class="card__title">
            Evenementgegevens
        </h2>
    </header>

    <div class="card__body">
        <div class="event-form-grid">
            <div class="form-group event-form-field--full">
                <label
                    for="titel"
                    class="form-label"
                >
                    Titel
                    <span class="event-form-required">*</span>
                </label>

                <input
                    type="text"
                    id="titel"
                    name="titel"
                    value="<?= $this->escape($titel) ?>"
                    class="form-control"
                    maxlength="255"
                    required
                    autofocus
                    autocomplete="off"
                >

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'titel'
                ) ?>
            </div>

            <div class="form-group">
                <label
                    for="status"
                    class="form-label"
                >
                    Status
                    <span class="event-form-required">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-control"
                    aria-describedby="status-help"
                    required
                >
                    <?php foreach (
                        Event::statusOptions() as $optionValue => $optionLabel
                    ): ?>
                        <option
                            value="<?= $this->escape($optionValue) ?>"
                            <?= $status === $optionValue
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $this->escape($optionLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small
                    id="status-help"
                    class="event-form-help"
                >
                    Alleen concepten zijn niet zichtbaar voor gewone leden.
                    Bij annulering worden betrokken leden eerst per mail verwittigd;
                    actieve inschrijvingen en shifts worden na succesvolle aflevering geannuleerd.
                </small>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'status'
                ) ?>
            </div>

            <div class="form-group">
                <label
                    for="max_deelnemers"
                    class="form-label"
                >
                    Maximum deelnemers
                </label>

                <input
                    type="number"
                    id="max_deelnemers"
                    name="max_deelnemers"
                    value="<?= $this->escape(
                        (string) $maxDeelnemers
                    ) ?>"
                    class="form-control"
                    min="1"
                    step="1"
                    inputmode="numeric"
                    aria-describedby="max-deelnemers-help"
                >

                <small
                    id="max-deelnemers-help"
                    class="event-form-help"
                >
                    Laat leeg wanneer er geen algemene deelnemerslimiet is.
                </small>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'max_deelnemers'
                ) ?>
            </div>

            <div class="form-group event-form-field--full">
                <label
                    for="beschrijving"
                    class="form-label"
                >
                    Beschrijving
                </label>

                <textarea
                    id="beschrijving"
                    name="beschrijving"
                    rows="6"
                    class="form-control event-form-textarea"
                ><?= $this->escape($beschrijving) ?></textarea>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'beschrijving'
                ) ?>
            </div>

            <div class="form-group event-form-field--full">
                <label
                    for="locatie"
                    class="form-label"
                >
                    Locatie
                </label>

                <input
                    type="text"
                    id="locatie"
                    name="locatie"
                    value="<?= $this->escape($locatie) ?>"
                    class="form-control"
                    maxlength="255"
                    autocomplete="off"
                >

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'locatie'
                ) ?>
            </div>

            <div class="form-group">
                <label
                    for="startdatum"
                    class="form-label"
                >
                    Startdatum
                    <span class="event-form-required">*</span>
                </label>

                <input
                    type="text"
                    id="startdatum"
                    name="startdatum"
                    value="<?= $this->escape($startdatum) ?>"
                    class="form-control"
                    placeholder="DD/mm/YYYY"
                    pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                    maxlength="10"
                    autocomplete="off"
                    required
                >

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'startdatum'
                ) ?>
            </div>

            <div class="form-group">
                <label
                    for="einddatum"
                    class="form-label"
                >
                    Einddatum
                </label>

                <input
                    type="text"
                    id="einddatum"
                    name="einddatum"
                    value="<?= $this->escape($einddatum) ?>"
                    class="form-control"
                    placeholder="DD/mm/YYYY"
                    pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                    maxlength="10"
                    autocomplete="off"
                    aria-describedby="einddatum-help"
                >

                <small
                    id="einddatum-help"
                    class="event-form-help"
                >
                    Laat leeg voor een evenement van één dag.
                </small>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'einddatum'
                ) ?>
            </div>
        </div>
    </div>

    <div class="card__body event-shift-builder" data-event-shift-builder>
        <div class="event-shift-builder__heading">
            <div>
                <h3>Shifts</h3>
                <p>
                    Voeg optioneel meteen shifts toe. Vrijwilligers worden later uitsluitend door een administrator toegewezen.
                </p>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                data-add-event-shift
                <?= $shiftTypes === [] ? 'disabled' : '' ?>
            >
                Shift toevoegen
            </button>
        </div>

        <?php if ($shifts !== []): ?>
            <div class="event-existing-shifts">
                <strong>Bestaande shifts</strong>
                <div>
                    <?php foreach ($shifts as $existingShift): ?>
                        <a href="<?= $this->escape(
                            $helpers->url->to(
                                '/shifts/' . $existingShift->shiftId
                            )
                        ) ?>">
                            <?= $this->escape($existingShift->displayNaam()) ?>
                            · <?= $this->escape($existingShift->displayPeriode()) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="event-shift-rows" data-event-shift-rows>
            <?php foreach ($oldShiftRows as $index => $row): ?>
                <?= $this->component(
                    'events/shift-row',
                    [
                        'shiftTypes' => $shiftTypes,
                        'index' => $index,
                        'row' => $row,
                        'defaultShiftCompensation' => $defaultShiftCompensation,
                    ]
                ) ?>
            <?php endforeach; ?>
        </div>

        <template data-event-shift-template>
            <?= $this->component(
                'events/shift-row',
                [
                    'shiftTypes' => $shiftTypes,
                    'index' => '__INDEX__',
                    'row' => [],
                    'defaultShiftCompensation' => $defaultShiftCompensation,
                ]
            ) ?>
        </template>
    </div>

    <footer class="card__footer event-form-actions">
        <a
            href="<?= $this->escape(
                $event !== null
                    ? $helpers->url->to(
                        '/events/' . $event->eventId
                    )
                    : $helpers->url->to('/events')
            ) ?>"
            class="btn btn-secondary"
        >
            Annuleren
        </a>

        <button
            type="submit"
            class="btn btn-success"
        >
            <?= $event !== null
                ? 'Evenement opslaan'
                : 'Evenement aanmaken' ?>
        </button>
    </footer>
</section>

<style>
    .event-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.1rem 1.25rem;
    }

    .event-form-field--full {
        grid-column: 1 / -1;
    }

    .event-form-required {
        color: var(--color-primary, #b5121b);
    }

    .event-form-textarea {
        min-height: 150px;
        resize: vertical;
    }

    .event-group-compensation {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 1rem;
        cursor: pointer;
        background: #f8fafc;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: var(--radius-medium, 0.5rem);
    }

    .event-group-compensation input {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
        margin-top: 0.1rem;
    }

    .event-group-compensation span,
    .event-group-compensation small {
        display: block;
    }

    .event-group-compensation small {
        margin-top: 0.25rem;
        color: var(--color-text-muted, #64748b);
        line-height: 1.45;
    }

    .event-form-help {
        display: block;
        margin-top: 0.1rem;
        color: var(--color-text-muted, #64748b);
        font-size: 0.8rem;
        line-height: 1.4;
    }

    .event-form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .event-shift-builder {
        display: grid;
        gap: 1rem;
        border-top: 1px solid var(--color-border, #e2e8f0);
    }

    .event-shift-builder__heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .event-shift-builder__heading h3,
    .event-shift-builder__heading p {
        margin: 0;
    }

    .event-shift-builder__heading p {
        margin-top: 0.35rem;
        color: var(--color-text-muted, #64748b);
    }

    .event-existing-shifts {
        display: grid;
        gap: 0.55rem;
        padding: 1rem;
        background: #f8fafc;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: var(--radius-medium, 0.5rem);
    }

    .event-existing-shifts div {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem 1rem;
    }

    .event-shift-rows {
        display: grid;
        gap: 1rem;
    }

    .event-shift-row {
        display: grid;
        gap: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: var(--radius-medium, 0.5rem);
    }

    .event-shift-row__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .event-shift-remove {
        min-height: 34px;
        padding: 0.4rem 0.7rem;
        font-size: 0.8rem;
    }

    .event-shift-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .event-shift-field--full {
        grid-column: span 2;
    }

    @media (max-width: 760px) {
        .event-form-grid {
            grid-template-columns: 1fr;
        }

        .event-form-field--full {
            grid-column: auto;
        }

        .event-form-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .event-form-actions .btn {
            width: 100%;
        }

        .event-shift-builder__heading,
        .event-shift-row__header {
            align-items: stretch;
            flex-direction: column;
        }

        .event-shift-grid {
            grid-template-columns: 1fr;
        }

        .event-shift-field--full {
            grid-column: auto;
        }
    }
</style>

<script>
    (() => {
        const builder = document.querySelector('[data-event-shift-builder]');

        if (!(builder instanceof HTMLElement)) {
            return;
        }

        const rows = builder.querySelector('[data-event-shift-rows]');
        const template = builder.querySelector('[data-event-shift-template]');
        const addButton = builder.querySelector('[data-add-event-shift]');

        if (!(rows instanceof HTMLElement)
            || !(template instanceof HTMLTemplateElement)
            || !(addButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        let nextIndex = rows.querySelectorAll('[data-event-shift-row]').length;

        const bindRemove = (row) => {
            const removeButton = row.querySelector('[data-remove-event-shift]');

            if (removeButton instanceof HTMLButtonElement) {
                removeButton.addEventListener('click', () => row.remove());
            }
        };

        rows.querySelectorAll('[data-event-shift-row]').forEach(bindRemove);

        addButton.addEventListener('click', () => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML.replaceAll(
                '__INDEX__',
                String(nextIndex++)
            ).trim();

            const row = wrapper.firstElementChild;

            if (!(row instanceof HTMLElement)) {
                return;
            }

            const dateInput = row.querySelector('[data-event-shift-date]');
            const eventStart = document.querySelector('#startdatum');

            if (dateInput instanceof HTMLInputElement
                && eventStart instanceof HTMLInputElement
            ) {
                dateInput.value = eventStart.value;
            }

            bindRemove(row);
            rows.append(row);
        });
    })();
</script>
