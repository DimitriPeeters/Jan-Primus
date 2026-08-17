<?php



$name ??= '';
$value ??= '';
$label ??= '';
$type ??= 'text';
$required ??= false;
$placeholder ??= '';

?>

<div class="form-group">

    <?php if ($label !== ''): ?>

        <label for="<?= $name ?>">

            <?= htmlspecialchars($label) ?>

            <?php if ($required): ?>

                <span class="required">*</span>

            <?php endif; ?>

        </label>

    <?php endif; ?>

    <input

        id="<?= $name ?>"

        name="<?= $name ?>"

        type="<?= $type ?>"

        value="<?= htmlspecialchars((string)$value) ?>"

        placeholder="<?= htmlspecialchars($placeholder) ?>"

        <?= $required ? 'required' : '' ?>

    >

</div>