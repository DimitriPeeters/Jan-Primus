<?php


use AEFS\Core\View\Component\Slot;
use AEFS\Core\View\Component\SlotBag;

/** @var Slot $slot */
/** @var SlotBag $slots */
/** @var string|null $class */

$groupClass = trim('form-group ' . ($class ?? ''));
?>
<div class="<?= $this->escape($groupClass) ?>">
    <?php if ($slots->has('label')): ?>
        <div class="form-group__label">
            <?= $slots->get('label') ?>
        </div>
    <?php endif; ?>

    <div class="form-group__control">
        <?= $slot ?>
    </div>

    <?php if ($slots->has('error')): ?>
        <div class="form-group__error">
            <?= $slots->get('error') ?>
        </div>
    <?php endif; ?>

    <?php if ($slots->has('help')): ?>
        <div class="form-group__help">
            <?= $slots->get('help') ?>
        </div>
    <?php endif; ?>
</div>