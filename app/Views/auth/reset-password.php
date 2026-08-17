<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var string $token */
/** @var bool $tokenValid */

$this->extend('layouts.guest', [
    'title' => $title ?? 'Nieuw wachtwoord instellen',
]);
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
            <h1>Nieuw wachtwoord instellen</h1>
            <p>
                <?php if ($tokenValid): ?>
                    Kies een nieuw wachtwoord van minstens 8 tekens.
                <?php else: ?>
                    Deze herstelkoppeling is ongeldig, verlopen of werd al
                    gebruikt.
                <?php endif; ?>
            </p>
        </header>

        <?php if ($tokenValid): ?>
            <div class="card__body">
                <?= $helpers->form->open(
                    $helpers->url->to(
                        '/reset-password/' . rawurlencode($token)
                    ),
                    'POST',
                    [
                        'class' => 'form',
                        'autocomplete' => 'off',
                    ]
                ) ?>

                <div class="form-group">
                    <?= $helpers->form->label(
                        'password',
                        'Nieuw wachtwoord',
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
                            'minlength' => 8,
                            'autocomplete' => 'new-password',
                            'autofocus' => true,
                        ]
                    ) ?>
                </div>

                <div class="form-group">
                    <?= $helpers->form->label(
                        'password_confirmation',
                        'Nieuw wachtwoord herhalen',
                        [
                            'class' => 'form-label',
                        ]
                    ) ?>

                    <?= $helpers->form->password(
                        'password_confirmation',
                        [
                            'id' => 'password_confirmation',
                            'class' => 'form-control',
                            'required' => true,
                            'minlength' => 8,
                            'autocomplete' => 'new-password',
                        ]
                    ) ?>
                </div>

                <?= $helpers->form->button(
                    'Wachtwoord opslaan',
                    'submit',
                    [
                        'class' => 'button button--primary button--block',
                    ]
                ) ?>

                <?= $helpers->form->close() ?>
            </div>
        <?php else: ?>
            <div class="card__body auth-card__expired">
                <a
                    class="button button--primary button--block"
                    href="<?= $this->escape(
                        $helpers->url->to('/forgot-password')
                    ) ?>"
                >
                    Nieuwe herstelmail aanvragen
                </a>
            </div>
        <?php endif; ?>

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
    .auth-card__expired,
    .auth-card__footer-link {
        text-align: center;
    }
</style>

<?php $this->endSection(); ?>
