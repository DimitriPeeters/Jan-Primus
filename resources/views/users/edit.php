<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var AEFS\Models\User $gebruiker */
/** @var array $leden */
/** @var array $errors */

?>

<?= component('page-header', [

    'title' => $gebruiker->fullName(),

    'subtitle' => 'Gebruiker wijzigen',

]) ?>

<?php if (!empty($errors)): ?>

    <?= component('alert', [

        'type' => 'danger',

        'message' => implode('<br>', $errors),

    ]) ?>

<?php endif; ?>

<form
    method="post"
    action="<?= Url::to('/users/' . $gebruiker->gebruikerId) ?>"
>

    <?= csrf_field() ?>

    <?= method_field('PUT') ?>

    <?= component('card', [

        'title' => 'Gebruikersgegevens',

        'content' => component('users/form', [

            'gebruiker' => $gebruiker,

            'leden' => $leden,

        ]),

    ]) ?>

</form>