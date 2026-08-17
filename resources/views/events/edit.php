<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var \AEFS\Models\Event $event */
/** @var array $errors */

?>

<?= component('page-header', [

    'title' => $event->titel,

    'subtitle' => 'Evenement wijzigen',

]) ?>

<?php if (!empty($errors)): ?>

    <?= component('alert', [

        'type' => 'danger',

        'message' => implode('<br>', $errors),

    ]) ?>

<?php endif; ?>

<form
    method="post"
    action="<?= Url::to('/events/' . $event->eventId) ?>"
>

    <?= csrf_field() ?>

    <?= method_field('PUT') ?>

    <?php require __DIR__ . '/form.php'; ?>

</form>