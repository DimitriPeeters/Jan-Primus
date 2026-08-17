<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */

$messages = $helpers->flash->messages();
$styles = [
    'success' => 'background:#dcfce7;color:#166534;border:1px solid #86efac;',
    'error' => 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;',
    'warning' => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d;',
    'info' => 'background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;',
];
?>

<?php foreach ($messages as $message): ?>
    <div
        role="alert"
        style="<?= $this->escape(
            $styles[$message->type] ?? $styles['info']
        ) ?>padding:14px 18px;border-radius:8px;margin-bottom:20px;"
    >
        <?= $this->escape($message->message) ?>
    </div>
<?php endforeach; ?>
