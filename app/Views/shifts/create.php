<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\ShiftType;

/** @var ViewHelpers $helpers */
/** @var Event[] $events */
/** @var ShiftType[] $shiftTypes */
/** @var int|null $selectedEventId */
/** @var string|null $defaultShiftCompensation */
/** @var string|null $groupSupplement */
/** @var string|null $title */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Nieuwe shift',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="shift-form-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Nieuwe shift',
            'subtitle' => 'Plan een functie, datum, tijdvak en vereiste bezetting.',
        ]
    ) ?>

    <form
        method="post"
        action="<?= $this->escape($helpers->url->to('/shifts/store')) ?>"
        novalidate
    >
        <?= $helpers->csrf->field() ?>

        <?= $this->component(
            'shifts/form',
            [
                'shift' => null,
                'events' => $events,
                'shiftTypes' => $shiftTypes,
                'selectedEventId' => $selectedEventId ?? 0,
                'defaultShiftCompensation' => $defaultShiftCompensation ?? '30.00',
                'groupSupplement' => $groupSupplement ?? '10.00',
            ]
        ) ?>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .shift-form-page {
        display: grid;
        gap: 1.25rem;
    }
</style>
<?php $this->endSection(); ?>
