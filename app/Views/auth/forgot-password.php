<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */

$this->extend('layouts.guest', [
    'title' => $title ?? 'Wachtwoord vergeten',
]);

$email = (string) $helpers->old->get('email', '');
?>

<?php $this->startSection('content'); ?>

<div class="auth">
    <section class="card auth-card">
        <div class="auth-card__brand">
            <img
                class="auth-card__logo"
                src="<?= $this->escape(
                    $helpers->asset->url('images/aefs-logo-white.png')
                ) ?>"
                alt="AEFS"
            >
        </div>

        <header class="auth-card__header">
            <h1>Wachtwoord vergeten?</h1>
            <p>
                Vul het e-mailadres van je account in. Je ontvangt een
                persoonlijke herstelkoppeling als het account actief is.
            </p>
        </header>

        <div class="card__body">
            <?= $helpers->form->open(
                $helpers->url->to('/forgot-password'),
                'POST',
                [
                    'class' => 'form',
                    'autocomplete' => 'on',
                ]
            ) ?>

            <div class="form-group">
                <?= $helpers->form->label(
                    'email',
                    'E-mailadres',
                    [
                        'class' => 'form-label',
                    ]
                ) ?>

                <?= $helpers->form->email(
                    'email',
                    $email,
                    [
                        'id' => 'email',
                        'class' => 'form-control',
                        'required' => true,
                        'autocomplete' => 'email',
                        'autofocus' => true,
                    ]
                ) ?>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'email'
                ) ?>
            </div>

            <?= $helpers->form->button(
                'Herstelmail aanvragen',
                'submit',
                [
                    'class' => 'button button--primary button--block',
                ]
            ) ?>

            <?= $helpers->form->close() ?>
        </div>

        <footer class="card__footer auth-card__footer-link">
            <a href="<?= $this->escape(
                $helpers->url->to('/login')
            ) ?>">
                Terug naar aanmelden
            </a>
        </footer>
    </section>
</div>

<style>
    .auth-card__footer-link {
        text-align: center;
    }
</style>

<?php $this->endSection(); ?>
