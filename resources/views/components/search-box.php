<?php

declare(strict_types=1);

$action ??= '';

$value ??= '';

$placeholder ??= 'Zoeken...';

?>

<form

    method="get"

    action="<?= $action ?>"

>

    <input

        type="search"

        name="zoek"

        value="<?= htmlspecialchars($value) ?>"

        placeholder="<?= htmlspecialchars($placeholder) ?>"

    >

</form>