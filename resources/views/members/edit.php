<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var \AEFS\Models\Member $lid */
/** @var array $errors */

?>

<?= component('page-header', [

    'title' => $lid->fullName(),

    'subtitle' => 'Lid wijzigen'

]) ?>

<?php if (!empty($errors)): ?>

    <?= component('alert', [

        'type' => 'danger',

        'message' => implode('<br>', $errors)

    ]) ?>

<?php endif; ?>

<form
    method="post"
    action="<?= Url::to('/members/' . $lid->lidId) ?>"
>

    <?= csrf_field() ?>

    <?= method_field('PUT') ?>

    <?= component('card', [

        'title' => 'Lidgegevens',

        'content' => component('members/form', [

            'lid' => $lid

        ])

    ]) ?>

</form>