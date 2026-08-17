<?php

/** @var string|null $title */
/** @var string|null $content */
/** @var string|null $footer */

$title = isset($title)
    ? trim((string) $title)
    : '';

$content = isset($content)
    ? (string) $content
    : '';

$footer = isset($footer)
    ? (string) $footer
    : '';
?>

<section class="card">
    <?php if ($title !== ''): ?>
        <header class="card__header">
            <h2 class="card__title">
                <?= $this->escape($title) ?>
            </h2>
        </header>
    <?php endif; ?>

    <div class="card__body">
        <?= $content ?>
    </div>

    <?php if ($footer !== ''): ?>
        <footer class="card__footer">
            <?= $footer ?>
        </footer>
    <?php endif; ?>
</section>
