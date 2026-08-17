<?php



$name ??= '';

$label ??= '';

$checked ??= false;

?>

<div class="form-check">

    <input

        type="checkbox"

        id="<?= $name ?>"

        name="<?= $name ?>"

        value="1"

        <?= $checked ? 'checked' : '' ?>

    >

    <label for="<?= $name ?>">

        <?= htmlspecialchars($label) ?>

    </label>

</div>