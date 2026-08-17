<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var array<int, App\Models\Member> $leden */
/** @var string|null $title */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Nieuwe gebruiker',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="user-page">
    <header>
        <h1>Nieuwe gebruiker</h1>
        <p>Koppel een gebruikersaccount aan een lid.</p>
    </header>

    <section class="card">
        <form
            method="post"
            action="<?= $this->escape(
                $helpers->url->to('/users')
            ) ?>"
            novalidate
        >
            <?= $this->component(
                'users/form',
                [
                    'leden' => $leden,
                ]
            ) ?>
        </form>
    </section>
</div>
<?php $this->endSection(); ?>
