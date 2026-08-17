<?php

declare(strict_types=1);

$title ??= 'Geen gegevens gevonden';

$text ??= '';

$button = $button ?? '';

?>

<div class="empty-state">

    <div class="empty-state-icon">

        <?= icon('folder-open') ?>

    </div>

    <h2>

        <?= htmlspecialchars($title) ?>

    </h2>

    <?php if ($text): ?>

        <p>

            <?= htmlspecialchars($text) ?>

        </p>

    <?php endif; ?>

    <?php if ($button): ?>

        <div class="mt-4">

            <?= $button ?>

        </div>

    <?php endif; ?>

</div>