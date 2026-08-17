<?php

declare(strict_types=1);

use AEFS\Core\Url;
use AEFS\Models\User;

/**
 * @var User|null $gebruiker
 * @var array $leden
 */

$isEdit = isset($gebruiker);
?>

<div class="row">
    <div class="col-md-6">
        <?= component('select', [
            'name' => 'lid_id',
            'label' => 'Lid',
            'required' => true,
            'value' => $gebruiker->lidId ?? '',
            'options' => array_reduce(
                $leden,
                static function (array $options, $lid): array {
                    $options[$lid->lidId] = $lid->fullName();

                    return $options;
                },
                [
                    '' => '-- Selecteer een lid --',
                ]
            ),
        ]) ?>
    </div>

    <div class="col-md-6">
        <?= component('input', [
            'name' => 'email',
            'label' => 'E-mailadres',
            'type' => 'email',
            'required' => true,
            'value' => $gebruiker->email ?? '',
        ]) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?= component('input', [
            'name' => 'password',
            'label' => $isEdit
                ? 'Nieuw wachtwoord'
                : 'Wachtwoord',
            'type' => 'password',
            'required' => !$isEdit,
        ]) ?>
    </div>

    <div class="col-md-6">
        <?= component('select', [
            'name' => 'rol',
            'label' => 'Rol',
            'required' => true,
            'value' => $gebruiker->rol ?? '',
            'options' => [
                User::ROLE_ADMIN => 'Administrator',
                User::ROLE_MEMBER => 'Lid',
            ],
        ]) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?= component('checkbox', [
            'name' => 'actief',
            'label' => 'Actief',
            'checked' => $gebruiker->actief ?? true,
        ]) ?>
    </div>

    <div class="col-md-6">
        <?= component('checkbox', [
            'name' => 'mail_blacklist',
            'label' => 'Mail blacklist',
            'checked' => $gebruiker->mailBlacklist ?? false,
        ]) ?>
    </div>
</div>

<hr>

<div class="d-flex justify-content-between">
    <?= component('button', [
        'href' => Url::to('/users'),
        'text' => 'Annuleren',
        'type' => 'secondary',
    ]) ?>

    <?= component('button', [
        'text' => $isEdit
            ? 'Gebruiker opslaan'
            : 'Gebruiker aanmaken',
        'icon' => 'save',
        'type' => 'success',
    ]) ?>
</div>
