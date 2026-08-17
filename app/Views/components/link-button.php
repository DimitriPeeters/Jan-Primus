<?php


use AEFS\Core\View\Component\Slot;

/** @var Slot $slot */
/** @var string $href */
/** @var string|null $variant */
/** @var string|null $class */

$buttonVariant = $variant ?? 'primary';
$buttonClass = trim(
    sprintf(
        'button button--%s %s',
        $buttonVariant,
        $class ?? ''
    )
);
?>
<a
    href="<?= $this->escape($href) ?>"
    class="<?= $this->escape($buttonClass) ?>"
>
    <?= $slot ?>
</a>