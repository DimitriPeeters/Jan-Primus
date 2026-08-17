<?php



$title ??= '';

$value ??= 0;

$icon ??= '';

$color ??= 'primary';

?>

<div class="stat-card">

    <div class="stat-card-icon <?= $color ?>">

        <?= $icon ?>

    </div>

    <div class="stat-card-content">

        <div class="stat-card-value">

            <?= htmlspecialchars((string)$value) ?>

        </div>

        <div class="stat-card-title">

            <?= htmlspecialchars($title) ?>

        </div>

    </div>

</div>