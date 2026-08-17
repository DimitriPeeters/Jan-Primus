<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\User;

/** @var ViewHelpers $helpers */
/** @var User[] $gebruikers */
/** @var User[] $wachtendeGebruikers */
/** @var User[] $goedgekeurdeGebruikers */
/** @var array{wachtend: int, actief: int, inactief: int} $statistieken */
/** @var string $zoekterm */
/** @var string|null $title */

$gebruikers ??= [];
$zoekterm ??= '';

$wachtendeGebruikers ??= array_values(
    array_filter(
        $gebruikers,
        static fn (User $user): bool => $user->isPending()
    )
);

$goedgekeurdeGebruikers ??= array_values(
    array_filter(
        $gebruikers,
        static fn (User $user): bool => !$user->isPending()
    )
);

$statistieken ??= [
    'wachtend' => count($wachtendeGebruikers),
    'actief' => count(
        array_filter(
            $goedgekeurdeGebruikers,
            static fn (User $user): bool => $user->isActive()
        )
    ),
    'inactief' => count(
        array_filter(
            $goedgekeurdeGebruikers,
            static fn (User $user): bool => !$user->isActive()
        )
    ),
];

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Gebruikers',
    ]
);
?>

<?php $this->startSection('styles'); ?>
<style>
    .users-page {
        display: grid;
        gap: 1.25rem;
    }

    .users-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .users-summary__item {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        background: var(--color-surface, #ffffff);
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: var(--radius-large, 12px);
        box-shadow: var(--shadow-card);
    }

    .users-summary__icon {
        display: flex;
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 800;
    }

    .users-summary__icon--pending {
        color: #854d0e;
        background: #fef3c7;
    }

    .users-summary__icon--active {
        color: #166534;
        background: #dcfce7;
    }

    .users-summary__icon--inactive {
        color: #475569;
        background: #e2e8f0;
    }

    .users-summary__content {
        display: grid;
        gap: 0.15rem;
        min-width: 0;
    }

    .users-summary__value {
        color: var(--color-text, #0f172a);
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1;
    }

    .users-summary__label {
        color: var(--color-text-muted, #64748b);
        font-size: 0.82rem;
        font-weight: 600;
    }

    .pending-card {
        border-color: #f2c94c;
    }

    .pending-card .card__header {
        background: #fffbeb;
        border-bottom: 1px solid #fde68a;
    }

    .pending-title {
        margin: 0;
        color: var(--color-text, #0f172a);
        font-size: 1rem;
        font-weight: 700;
    }

    .pending-clear {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem;
        color: #166534;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
    }

    .pending-clear__mark {
        display: flex;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: #15803d;
        border-radius: 50%;
        font-weight: 800;
    }

    .pending-clear strong,
    .pending-clear p {
        margin: 0;
    }

    .pending-clear p {
        margin-top: 0.15rem;
        color: #166534;
        font-size: 0.85rem;
    }

    @media (max-width: 900px) {
        .users-summary {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('content'); ?>
<div class="users-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Gebruikers',
            'subtitle' => 'Keur nieuwe registraties goed en beheer de rollen van bestaande accounts.',
        ]
    ) ?>

    <div class="users-summary">
        <div class="users-summary__item">
            <div class="users-summary__icon users-summary__icon--pending">
                !
            </div>

            <div class="users-summary__content">
                <span class="users-summary__value">
                    <?= (int) $statistieken['wachtend'] ?>
                </span>
                <span class="users-summary__label">
                    Wacht op goedkeuring
                </span>
            </div>
        </div>

        <div class="users-summary__item">
            <div class="users-summary__icon users-summary__icon--active">
                ✓
            </div>

            <div class="users-summary__content">
                <span class="users-summary__value">
                    <?= (int) $statistieken['actief'] ?>
                </span>
                <span class="users-summary__label">
                    Actieve accounts
                </span>
            </div>
        </div>

        <div class="users-summary__item">
            <div class="users-summary__icon users-summary__icon--inactive">
                –
            </div>

            <div class="users-summary__content">
                <span class="users-summary__value">
                    <?= (int) $statistieken['inactief'] ?>
                </span>
                <span class="users-summary__label">
                    Inactieve accounts
                </span>
            </div>
        </div>
    </div>

    <section class="card pending-card">
        <header class="card__header">
            <h2 class="pending-title">
                Wachtende registraties
                <?php if ($wachtendeGebruikers !== []): ?>
                    (<?= count($wachtendeGebruikers) ?>)
                <?php endif; ?>
            </h2>
        </header>

        <div class="card__body">
            <?php if ($wachtendeGebruikers === []): ?>
                <div class="pending-clear">
                    <div class="pending-clear__mark">✓</div>

                    <div>
                        <strong>Alles is verwerkt</strong>
                        <p>
                            Er zijn momenteel geen registraties die op
                            goedkeuring wachten.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <?= $this->component(
                    'users/table',
                    [
                        'gebruikers' => $wachtendeGebruikers,
                        'quickApprove' => true,
                        'emptyTitle' => 'Geen wachtende registraties',
                        'emptyText' => 'Er zijn geen registraties die op goedkeuring wachten.',
                    ]
                ) ?>
            <?php endif; ?>
        </div>
    </section>

    <?= $this->component(
        'card',
        [
            'content' => $this->component(
                'search-box',
                [
                    'action' => $helpers->url->to('/users'),
                    'clearUrl' => $helpers->url->to('/users'),
                    'value' => $zoekterm,
                    'label' => 'Gebruiker zoeken',
                    'placeholder' => 'Zoek op naam of e-mailadres...',
                ]
            ),
        ]
    ) ?>

    <?= $this->component(
        'card',
        [
            'title' => $zoekterm === ''
                ? 'Alle gebruikers'
                : 'Zoekresultaten',
            'content' => $this->component(
                'users/table',
                [
                    'gebruikers' => $gebruikers,
                    'quickApprove' => false,
                    'emptyTitle' => 'Geen gebruikers gevonden',
                    'emptyText' => 'Er zijn geen gebruikers die aan de zoekopdracht voldoen.',
                ]
            ),
        ]
    ) ?>
</div>
<?php $this->endSection(); ?>