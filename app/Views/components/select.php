<?php



$name ??= '';

$label ??= '';

$options ??= [];

$value ??= '';

?>

<div class="form-group">

    <?php if ($label): ?>

        <label><?= htmlspecialchars($label) ?></label>

    <?php endif; ?>

    <select name="<?= $name ?>">

        <?php foreach ($options as $key => $text): ?>

            <option

                value="<?= htmlspecialchars((string)$key) ?>"

                <?= ((string)$key === (string)$value) ? 'selected' : '' ?>

            >

                <?= htmlspecialchars((string)$text) ?>

            </option>

        <?php endforeach; ?>

    </select>

</div>