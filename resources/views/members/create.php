<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var array $errors */

?>

<?= component('page-header', [

    'title' => 'Nieuw lid',

    'subtitle' => 'Lid toevoegen'

]) ?>

<?php if (!empty($errors)): ?>

    <?= component('alert', [

        'type' => 'danger',

        'message' => implode('<br>', $errors)

    ]) ?>

<?php endif; ?>

<form
    method="post"
    action="<?= Url::to('/members') ?>"
>

    <?= csrf_field() ?>

    <?= component('card', [

        'title' => 'Lidgegevens',

        'content' => component('members/form')

    ]) ?>

</form>