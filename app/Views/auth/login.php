<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */

$this->extend('layouts.guest', [
    'title' => 'Aanmelden',
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
                    $helpers->asset->url(
                        'images/aefs-logo-white.png'
                    )
                ) ?>"
                alt="AEFS"
            >
        </div>

        <header class="auth-card__header">
            <h1>Aanmelden</h1>

            <p>
                Meld je aan bij AEFS Eventbeheer.
            </p>
        </header>

        <div class="card__body">
            <?= $helpers->form->open(
                $helpers->url->to('/login'),
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

            <div class="form-group">
                <?= $helpers->form->label(
                    'password',
                    'Wachtwoord',
                    [
                        'class' => 'form-label',
                    ]
                ) ?>

                <?= $helpers->form->password(
                    'password',
                    [
                        'id' => 'password',
                        'class' => 'form-control',
                        'required' => true,
                        'autocomplete' => 'current-password',
                    ]
                ) ?>

                <?= $helpers->errorRenderer->field(
                    $helpers->errors,
                    'password'
                ) ?>
            </div>

            <div class="form-group form-group--checkbox">
                <?= $helpers->form->checkbox(
                    'remember',
                    '1',
                    $helpers->old->get('remember') === '1',
                    [
                        'id' => 'remember',
                    ]
                ) ?>

                <?= $helpers->form->label(
                    'remember',
                    'Aangemeld blijven'
                ) ?>
            </div>

            <?= $helpers->form->button(
                'Aanmelden',
                'submit',
                [
                    'class' => 'button button--primary button--block',
                ]
            ) ?>

            <?= $helpers->form->close() ?>
        </div>

        <footer class="card__footer auth-card__footer-links">
            <a href="<?= $this->escape(
                $helpers->url->to('/register')
            ) ?>">
                Nog geen account? Registreren
            </a>

            <a href="<?= $this->escape(
                $helpers->url->to('/forgot-password')
            ) ?>">
                Wachtwoord vergeten?
            </a>
        </footer>
    </section>
</div>

<style>
    .auth-card__footer-links {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
</style>

<?php $this->endSection(); ?>