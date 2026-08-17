<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\ShiftType;

/** @var ViewHelpers $helpers */
/** @var string|null $title */
/** @var ShiftType[] $shiftTypes */
/** @var string|null $defaultShiftCompensation */
/** @var string|null $defaultGroupSupplement */
/** @var bool|null $defaultEventUsesGroups */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Nieuw evenement',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="event-form-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Nieuw evenement',
            'subtitle' => 'Maak een evenement aan en bepaal wanneer leden het kunnen bekijken.',
        ]
    ) ?>

    <form
        method="post"
        action="<?= $this->escape($helpers->url->to('/events/store')) ?>"
        novalidate
    >
        <?= $helpers->csrf->field() ?>

        <?= $this->component(
            'events/form',
            [
                'event' => null,
                'shiftTypes' => $shiftTypes,
                'shifts' => [],
                'defaultShiftCompensation' => $defaultShiftCompensation ?? '30.00',
                'defaultGroupSupplement' => $defaultGroupSupplement ?? '10.00',
                'defaultEventUsesGroups' => $defaultEventUsesGroups ?? false,
            ]
        ) ?>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .event-form-page {
        display: grid;
        gap: 1.25rem;
    }
</style>
<?php $this->endSection(); ?>
