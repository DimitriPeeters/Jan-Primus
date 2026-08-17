<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var array $errors */

$title = 'Nieuw evenement';

?>

<?= component('page-header', [

    'title' => 'Nieuw evenement',

    'subtitle' => 'Evenement aanmaken',

]) ?>

<?php if (!empty($errors)): ?>

    <?= component('alert', [

        'type' => 'danger',

        'message' => implode('<br>', $errors),

    ]) ?>

<?php endif; ?>

<form
    method="post"
    action="<?= Url::to('/events') ?>"
>

    <?= csrf_field() ?>

    <?php require __DIR__ . '/form.php'; ?>

</form>