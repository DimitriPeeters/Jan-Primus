<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\User;

/** @var ViewHelpers $helpers */
/** @var User $gebruiker */
/** @var array<int, array<string, mixed>> $logs */
/** @var string|null $title */

$logs ??= [];

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? $gebruiker->fullName(),
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="user-detail">
    <header class="user-detail__header">
        <div>
            <h1><?= $this->escape($gebruiker->fullName()) ?></h1>
            <p>Gebruikersaccount #<?= $gebruiker->gebruikerId ?></p>
        </div>

        <div class="user-detail__actions">
            <a
                href="<?= $this->escape(
                    $helpers->url->to('/users')
                ) ?>"
                class="btn btn-secondary"
            >
                Terug
            </a>

            <a
                href="<?= $this->escape(
                    $helpers->url->to(
                        '/users/'
                        . $gebruiker->gebruikerId
                        . '/edit'
                    )
                ) ?>"
                class="btn btn-primary"
            >
                Wijzigen
            </a>
        </div>
    </header>

    <div class="user-detail__grid">
        <section class="card">
            <h2>Account</h2>
            <table class="table">
                <tbody>
                <tr>
                    <th>Lid ID</th>
                    <td><?= $gebruiker->lidId ?></td>
                </tr>
                <tr>
                    <th>E-mailadres</th>
                    <td><?= $this->escape($gebruiker->email) ?></td>
                </tr>
                <tr>
                    <th>Rol</th>
                    <td><?= $this->escape($gebruiker->roleLabel()) ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?= $gebruiker->actief ? 'Actief' : 'Inactief' ?></td>
                </tr>
                <tr>
                    <th>Mail blacklist</th>
                    <td><?= $gebruiker->mailBlacklist ? 'Ja' : 'Nee' ?></td>
                </tr>
                </tbody>
            </table>
        </section>
    </div>

    <?php if ($logs !== []): ?>
        <?= $this->component(
            'audit-log',
            [
                'logs' => $logs,
            ]
        ) ?>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .user-detail {
        display: grid;
        gap: 1.25rem;
    }

    .user-detail__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .user-detail__header h1,
    .user-detail__header p {
        margin-top: 0;
    }

    .user-detail__actions {
        display: flex;
        gap: 0.75rem;
    }

    .user-detail__grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
    }

    @media (max-width: 760px) {
        .user-detail__header,
        .user-detail__actions {
            flex-direction: column;
        }
    }
</style>
<?php $this->endSection(); ?>
