<?php

declare(strict_types=1);

use AEFS\Core\Flash;

$messages = Flash::all();

if ($messages === []) {
    return;
}

$styles = [
    'success' => '#dcfce7;color:#166534;border:1px solid #86efac;',
    'error'   => '#fee2e2;color:#991b1b;border:1px solid #fca5a5;',
    'warning' => '#fef3c7;color:#92400e;border:1px solid #fcd34d;',
    'info'    => '#dbeafe;color:#1e40af;border:1px solid #93c5fd;',
];

foreach ($messages as $type => $items):

    foreach ($items as $message):
?>

<div
    style="
        background:<?= explode(';', $styles[$type])[0] ?>;
        <?= implode(';', array_slice(explode(';', $styles[$type]),1)) ?>
        padding:14px 18px;
        border-radius:8px;
        margin-bottom:20px;
    "
>

    <?= htmlspecialchars($message, ENT_QUOTES) ?>

</div>

<?php
    endforeach;

endforeach;