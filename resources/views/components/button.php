<?php

declare(strict_types=1);

$type ??= 'primary';

$text ??= '';

$href ??= null;

$icon ??= null;

$class = 'btn btn-'.$type;

?>

<?php if($href): ?>

<a

    href="<?= $href ?>"

    class="<?= $class ?>"

>

    <?php

    if($icon){

        echo icon($icon);

    }

    ?>

    <?= htmlspecialchars($text) ?>

</a>

<?php else: ?>

<button

    class="<?= $class ?>"

>

    <?php

    if($icon){

        echo icon($icon);

    }

    ?>

    <?= htmlspecialchars($text) ?>

</button>

<?php endif; ?>