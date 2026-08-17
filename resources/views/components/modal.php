<?php

declare(strict_types=1);

$id ??= '';

$title ??= '';

$content ??= '';

$footer ??= '';

?>

<div

id="<?= $id ?>"

class="modal"

>

<div class="modal-window">

<div class="modal-header">

<h2>

<?= htmlspecialchars($title) ?>

</h2>

</div>

<div class="modal-body">

<?= $content ?>

</div>

<?php if($footer): ?>

<div class="modal-footer">

<?= $footer ?>

</div>

<?php endif; ?>

</div>

</div>