<?php


use AEFS\Core\View\Component\Slot;

/** @var Slot $slot */
/** @var string|null $type */
/** @var string|null $class */

$alertType = $type ?? 'info';
$alertClass = trim(
    sprintf(
        'alert alert--%s %s',
        $alertType,
        $class ?? ''
    )
);
?>
<div
    class="<?= $this->escape($alertClass) ?>"
    role="alert"
>
    <?= $slot ?>
</div>