<?php

use App\Models\ShiftType;

/** @var ShiftType[] $shiftTypes */
/** @var int|string $index */
/** @var array<string, mixed> $row */

$shiftTypes ??= [];
$row ??= [];
$index ??= 0;
$defaultShiftCompensation ??= '30.00';

$typeId = (int) ($row['type_id'] ?? 0);

$fieldName = static fn(string $field): string => sprintf(
    'shifts[%s][%s]',
    $index,
    $field
);
$fieldId = static fn(string $field): string => sprintf(
    'shift_%s_%s',
    $index,
    $field
);
?>

<div class="event-shift-row" data-event-shift-row>
    <div class="event-shift-row__header">
        <strong>Nieuwe shift</strong>
        <button
            type="button"
            class="btn btn-secondary event-shift-remove"
            data-remove-event-shift
        >
            Verwijderen
        </button>
    </div>

    <div class="event-shift-grid">
        <div class="form-group">
            <label class="form-label" for="<?= $this->escape($fieldId('type_id')) ?>">
                Functie <span class="event-form-required">*</span>
            </label>
            <select
                id="<?= $this->escape($fieldId('type_id')) ?>"
                name="<?= $this->escape($fieldName('type_id')) ?>"
                class="form-control"
                required
            >
                <option value="">Kies een functie</option>
                <?php foreach ($shiftTypes as $type): ?>
                    <option
                        value="<?= $type->typeId ?>"
                        <?= $type->typeId === $typeId ? 'selected' : '' ?>
                    >
                        <?= $this->escape($type->naam) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="<?= $this->escape($fieldId('max_personen')) ?>">
                Vereist aantal vrijwilligers <span class="event-form-required">*</span>
            </label>
            <input
                type="number"
                id="<?= $this->escape($fieldId('max_personen')) ?>"
                name="<?= $this->escape($fieldName('max_personen')) ?>"
                value="<?= $this->escape((string) ($row['max_personen'] ?? 1)) ?>"
                class="form-control"
                min="1"
                step="1"
                required
            >
        </div>

        <div class="form-group event-shift-field--full">
            <label class="form-label" for="<?= $this->escape($fieldId('naam')) ?>">
                Interne shiftnaam
            </label>
            <input
                type="text"
                id="<?= $this->escape($fieldId('naam')) ?>"
                name="<?= $this->escape($fieldName('naam')) ?>"
                value="<?= $this->escape((string) ($row['naam'] ?? '')) ?>"
                class="form-control"
                maxlength="100"
                autocomplete="off"
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="<?= $this->escape($fieldId('shift_datum')) ?>">
                Datum <span class="event-form-required">*</span>
            </label>
            <input
                type="text"
                id="<?= $this->escape($fieldId('shift_datum')) ?>"
                name="<?= $this->escape($fieldName('shift_datum')) ?>"
                value="<?= $this->escape((string) ($row['shift_datum'] ?? '')) ?>"
                class="form-control"
                placeholder="DD/mm/YYYY"
                pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                maxlength="10"
                autocomplete="off"
                data-event-shift-date
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="<?= $this->escape($fieldId('starttijd')) ?>">
                Starttijd <span class="event-form-required">*</span>
            </label>
            <input
                type="text"
                id="<?= $this->escape($fieldId('starttijd')) ?>"
                name="<?= $this->escape($fieldName('starttijd')) ?>"
                value="<?= $this->escape((string) ($row['starttijd'] ?? '')) ?>"
                class="form-control"
                placeholder="UU:mm"
                pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]"
                maxlength="5"
                autocomplete="off"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="<?= $this->escape($fieldId('eindtijd')) ?>">
                Eindtijd <span class="event-form-required">*</span>
            </label>
            <input
                type="text"
                id="<?= $this->escape($fieldId('eindtijd')) ?>"
                name="<?= $this->escape($fieldName('eindtijd')) ?>"
                value="<?= $this->escape((string) ($row['eindtijd'] ?? '')) ?>"
                class="form-control"
                placeholder="UU:mm"
                pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]"
                maxlength="5"
                autocomplete="off"
                required
            >
        </div>
    </div>
</div>
