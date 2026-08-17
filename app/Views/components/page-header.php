<?php



$title ??= '';

$subtitle ??= '';

$actions ??= '';

?>

<div class="page-header">

    <div>

        <h1>

            <?= htmlspecialchars($title) ?>

        </h1>

        <?php if ($subtitle !== ''): ?>

            <p>

                <?= htmlspecialchars($subtitle) ?>

            </p>

        <?php endif; ?>

    </div>

    <?php if ($actions !== ''): ?>

        <div>

            <?= $actions ?>

        </div>

    <?php endif; ?>

</div>