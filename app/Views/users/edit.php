<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\User;

/** @var ViewHelpers $helpers */
/** @var User $gebruiker */
/** @var string|null $title */

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Account beheren',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="user-page">
    <header>
        <h1><?= $this->escape($gebruiker->fullName()) ?></h1>
        <p>Keur het account goed en beheer de gebruikersrol.</p>
    </header>

    <section class="card">
        <form
            method="post"
            action="<?= $this->escape(
                $helpers->url->to(
                    '/users/'
                    . $gebruiker->gebruikerId
                    . '/update'
                )
            ) ?>"
            novalidate
        >
            <?= $this->component(
                'users/form',
                [
                    'gebruiker' => $gebruiker,
                ]
            ) ?>
        </form>
    </section>
</div>
<?php $this->endSection(); ?>