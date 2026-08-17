<?php

$title ??= 'Geen gegevens gevonden';
$text ??= '';
$button ??= '';
$icon ??= 'folder-open';

$title = trim((string) $title);
$text = trim((string) $text);
$button = (string) $button;
$icon = trim((string) $icon);
?>

<div class="empty-state">
    <?php if ($icon !== ''): ?>
        <div
            class="empty-state__icon icon icon-<?= $this->escape($icon) ?>"
            aria-hidden="true"
        ></div>
    <?php endif; ?>

    <h2 class="empty-state__title">
        <?= $this->escape($title) ?>
    </h2>

    <?php if ($text !== ''): ?>
        <p class="empty-state__text">
            <?= $this->escape($text) ?>
        </p>
    <?php endif; ?>

    <?php if ($button !== ''): ?>
        <div class="empty-state__actions">
            <?= $this->raw($button) ?>
        </div>
    <?php endif; ?>
</div>