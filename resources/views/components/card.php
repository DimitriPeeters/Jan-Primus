<?php

declare(strict_types=1);

/**

$title

$content

$footer

*/

?>

<div class="card">

    <?php if(!empty($title)): ?>

        <div class="card-header">

            <div class="card-title">

                <?= htmlspecialchars($title) ?>

            </div>

        </div>

    <?php endif; ?>

    <div class="card-body">

        <?= $content ?? '' ?>

    </div>

    <?php if(!empty($footer)): ?>

        <div class="card-footer">

            <?= $footer ?>

        </div>

    <?php endif; ?>

</div>