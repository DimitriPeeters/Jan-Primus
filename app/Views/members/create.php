<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var string|null $title */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Nieuw lid',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="member-form-page">
    <header class="member-form-page__header">
        <div>
            <h1 class="member-form-page__title">
                Nieuw lid
            </h1>

            <p class="member-form-page__subtitle">
                Voeg een nieuw lid toe aan AEFS Eventbeheer.
            </p>
        </div>

        <a
            href="<?= $this->escape(
                $helpers->url->to('/members')
            ) ?>"
            class="btn btn-secondary"
        >
            Terug naar leden
        </a>
    </header>

    <form
        method="post"
        action="<?= $this->escape(
            $helpers->url->to('/members')
        ) ?>"
        class="member-form-page__form"
        autocomplete="off"
    >
        <?= $this->component(
            'members/form',
            [
                'submitText' => 'Lid aanmaken',
                'cancelUrl' => $helpers->url->to('/members'),
            ]
        ) ?>
    </form>
</div>
<?php $this->endSection(); ?>