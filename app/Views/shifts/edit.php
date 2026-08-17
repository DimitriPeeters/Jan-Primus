<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Event;
use App\Models\Shift;
use App\Models\ShiftType;

/** @var ViewHelpers $helpers */
/** @var Shift $shift */
/** @var Event[] $events */
/** @var ShiftType[] $shiftTypes */
/** @var string|null $defaultShiftCompensation */
/** @var string|null $groupSupplement */
/** @var string|null $title */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Shift wijzigen',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="shift-form-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Shift wijzigen',
            'subtitle' => $shift->displayPeriode(),
        ]
    ) ?>

    <?php if ($shift->isGeannuleerd()): ?>
        <div class="alert alert-warning" role="alert">
            Deze shift is geannuleerd. De status kan niet via dit formulier worden hersteld.
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?= $this->escape(
            $helpers->url->to('/shifts/' . $shift->shiftId . '/update')
        ) ?>"
        novalidate
    >
        <?= $helpers->csrf->field() ?>

        <?= $this->component(
            'shifts/form',
            [
                'shift' => $shift,
                'events' => $events,
                'shiftTypes' => $shiftTypes,
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
