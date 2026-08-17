<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var array $leden */
/** @var array $errors */

?>

<?= component('page-header', [

    'title' => 'Nieuwe gebruiker',

    'subtitle' => 'Gebruiker toevoegen',

]) ?>

<?php if (!empty($errors)): ?>

    <?= component('alert', [

        'type' => 'danger',

        'message' => implode('<br>', $errors),

    ]) ?>

<?php endif; ?>

<form
    method="post"
    action="<?= Url::to('/users') ?>"
>

    <?= csrf_field() ?>

    <?= component('card', [

        'title' => 'Gebruikersgegevens',

        'content' => component('users/form', [

            'leden' => $leden,

        ]),

    ]) ?>

</form>