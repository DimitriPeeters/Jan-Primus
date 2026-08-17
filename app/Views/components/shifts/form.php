<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\Shift;
use App\Models\ShiftType;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Event[] $events */
/** @var ShiftType[] $shiftTypes */
/** @var Shift|null $shift */

$events ??= [];
$shiftTypes ??= [];
$shift ??= null;
$selectedEventId ??= 0;
$defaultShiftCompensation ??= Shift::DEFAULT_COMPENSATION;
$groupSupplement ??= '10.00';

$oldInput = $helpers->old->all();

$value = static function (
    string $key,
    mixed $default = ''
) use ($oldInput): mixed {
    return array_key_exists($key, $oldInput)
        ? $oldInput[$key]
        : $default;
};

$start = $shift !== null
    ? new DateTimeImmutable($shift->startOp)
    : null;

$end = $shift !== null
    ? new DateTimeImmutable($shift->eindOp)
    : null;

$eventId = (int) $value(
    'event_id',
    $shift?->eventId ?? $selectedEventId
);

$typeId = (int) $value(
    'type_id',
    $shift?->typeId ?? 0
);

$naam = (string) $value(
    'naam',
    $shift?->naam ?? ''
);

$shiftDatum = (string) $value(
    'shift_datum',
    BelgianDateTime::formatDate($start, '')
);

$starttijd = (string) $value(
    'starttijd',
    $start?->format('H:i') ?? ''
);

$eindtijd = (string) $value(
    'eindtijd',
    $end?->format('H:i') ?? ''
);

$maxPersonen = (int) $value(
    'max_personen',
    $shift?->maxPersonen ?? 1
);

$vergoedingBedrag = (string) $value(
    'vergoeding_bedrag',
    $shift !== null
        ? number_format(
            (float) $shift->vergoedingBedrag,
            2,
            ',',
            ''
        )
        : number_format(
            (float) $defaultShiftCompensation,
            2,
            ',',
            ''
        )
);

$status = $shift?->status ?? Shift::STATUS_ACTIEF;
?>

<section class="card shift-form-card">
    <header class="card__header">
        <h2 class="card__title">Shiftgegevens</h2>
    </header>

    <div class="card__body">
        <input
            type="hidden"
            name="status"
            value="<?= $this->escape($status) ?>"
        >

        <div class="shift-form-grid">
            <div class="form-group shift-form-field--full">
                <label for="event_id" class="form-label">
                    Evenement
                    <span class="shift-form-required">*</span>
                </label>

                <select
                    id="event_id"
                    name="event_id"
                    class="form-control"
                    required
                >
                    <option value="">Kies een evenement</option>

                    <?php foreach ($events as $event): ?>
                        <?php
                        $disabled = $event->isCancelled()
                            && $event->eventId !== $eventId;
                        ?>
                        <option
                            value="<?= $event->eventId ?>"
                            <?= $event->eventId === $eventId
                                ? 'selected'
                                : '' ?>
                            <?= $disabled ? 'disabled' : '' ?>
                        >
                            <?= $this->escape($event->titel) ?>
                            · <?= $this->escape($event->displayDate()) ?>
                            <?= $event->isCancelled()
                                ? ' · geannuleerd'
                                : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'event_id'
                ) ?>
            </div>

            <div class="form-group">
                <label for="type_id" class="form-label">
                    Functie
                    <span class="shift-form-required">*</span>
                </label>

                <select
                    id="type_id"
                    name="type_id"
                    class="form-control"
                    required
                >
                    <option value="">Kies een functie</option>

                    <?php foreach ($shiftTypes as $type): ?>
                        <?php
                        $disabled = !$type->isActief()
                            && $type->typeId !== $typeId;
                        ?>
                        <option
                            value="<?= $type->typeId ?>"
                            <?= $type->typeId === $typeId
                                ? 'selected'
                                : '' ?>
                            <?= $disabled ? 'disabled' : '' ?>
                        >
                            <?= $this->escape($type->naam) ?>
                            <?= !$type->isActief()
                                ? ' · inactief'
                                : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'type_id'
                ) ?>
            </div>

            <div class="form-group">
                <label for="max_personen" class="form-label">
                    Vereist aantal vrijwilligers
                    <span class="shift-form-required">*</span>
                </label>

                <input
                    type="number"
                    id="max_personen"
                    name="max_personen"
                    value="<?= $maxPersonen ?>"
                    class="form-control"
                    min="1"
                    step="1"
                    inputmode="numeric"
                    required
                >

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'max_personen'
                ) ?>
            </div>

            <div class="form-group">
                <label for="vergoeding_bedrag" class="form-label">
                    Vergoeding per gewerkte shift
                    <span class="shift-form-required">*</span>
                </label>

                <input
                    type="text"
                    id="vergoeding_bedrag"
                    name="vergoeding_bedrag"
                    value="<?= $this->escape($vergoedingBedrag) ?>"
                    class="form-control"
                    placeholder="30,00"
                    pattern="[0-9]+(?:[,.][0-9]{1,2})?"
                    inputmode="decimal"
                    aria-describedby="shift-compensation-help"
                    required
                >

                <small id="shift-compensation-help" class="shift-form-help">
                    De huidige standaard is € <?= $this->escape(
                        number_format(
                            (float) $defaultShiftCompensation,
                            2,
                            ',',
                            ''
                        )
                    ) ?>. Bij een groepsevenement geldt de toeslag die op het evenement is vastgelegd
                    (standaard € <?= $this->escape(
                        number_format((float) $groupSupplement, 2, ',', '')
                    ) ?>).
                </small>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'vergoeding_bedrag'
                ) ?>
            </div>

            <div class="form-group shift-form-field--full">
                <label for="naam" class="form-label">
                    Interne shiftnaam
                </label>

                <input
                    type="text"
                    id="naam"
                    name="naam"
                    value="<?= $this->escape($naam) ?>"
                    class="form-control"
                    maxlength="100"
                    autocomplete="off"
                    aria-describedby="shift-name-help"
                >

                <small id="shift-name-help" class="shift-form-help">
                    Optioneel. Zonder naam wordt de gekozen functie als titel gebruikt.
                </small>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'naam'
                ) ?>
            </div>

            <div class="form-group">
                <label for="shift_datum" class="form-label">
                    Datum
                    <span class="shift-form-required">*</span>
                </label>

                <input
                    type="text"
                    id="shift_datum"
                    name="shift_datum"
                    value="<?= $this->escape($shiftDatum) ?>"
                    class="form-control"
                    placeholder="DD/mm/YYYY"
                    pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                    maxlength="10"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="form-group">
                <label for="starttijd" class="form-label">
                    Starttijd
                    <span class="shift-form-required">*</span>
                </label>

                <input
                    type="text"
                    id="starttijd"
                    name="starttijd"
                    value="<?= $this->escape($starttijd) ?>"
                    class="form-control"
                    placeholder="UU:mm"
                    pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]"
                    maxlength="5"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="form-group">
                <label for="eindtijd" class="form-label">
                    Eindtijd
                    <span class="shift-form-required">*</span>
                </label>

                <input
                    type="text"
                    id="eindtijd"
                    name="eindtijd"
                    value="<?= $this->escape($eindtijd) ?>"
                    class="form-control"
                    placeholder="UU:mm"
                    pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]"
                    maxlength="5"
                    autocomplete="off"
                    aria-describedby="shift-end-help"
                    required
                >

                <small id="shift-end-help" class="shift-form-help">
                    Een eindtijd vóór de starttijd wordt als de volgende dag geïnterpreteerd.
                </small>
            </div>
        </div>
    </div>

    <footer class="card__footer shift-form-actions">
        <a
            href="<?= $this->escape(
                $shift !== null
                    ? $helpers->url->to('/shifts/' . $shift->shiftId)
                    : $helpers->url->to('/shifts')
            ) ?>"
            class="btn btn-secondary"
        >
            Annuleren
        </a>

        <button type="submit" class="btn btn-success">
            <?= $shift !== null
                ? 'Shift opslaan'
                : 'Shift aanmaken' ?>
        </button>
    </footer>
</section>

<style>
    .shift-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.1rem 1.25rem;
    }

    .shift-form-field--full {
        grid-column: 1 / -1;
    }

    .shift-form-required {
        color: var(--color-primary);
    }

    .shift-form-help {
        display: block;
        color: var(--color-text-muted);
        font-size: 0.8rem;
        line-height: 1.4;
    }

    .shift-form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 760px) {
        .shift-form-grid {
            grid-template-columns: 1fr;
        }

        .shift-form-field--full {
            grid-column: auto;
        }

        .shift-form-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .shift-form-actions .btn {
            width: 100%;
        }
    }
</style>
