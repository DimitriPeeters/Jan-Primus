<?php

declare(strict_types=1);

$name ??= '';

$label ??= '';

$value ??= '';

$rows ??= 5;

?>

<div class="form-group">

    <?php if ($label): ?>

        <label><?= htmlspecialchars($label) ?></label>

    <?php endif; ?>

    <textarea

        name="<?= $name ?>"

        rows="<?= $rows ?>"

    ><?= htmlspecialchars((string)$value) ?></textarea>

</div>