<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\User;

/** @var ViewHelpers $helpers */
/** @var User[] $gebruikers */
/** @var bool $quickApprove */
/** @var string $emptyTitle */
/** @var string $emptyText */

$gebruikers ??= [];
$quickApprove ??= false;
$emptyTitle ??= 'Geen gebruikers gevonden';
$emptyText ??= 'Er zijn geen gebruikers die aan de zoekopdracht voldoen.';
?>

<?php if ($gebruikers === []): ?>
    <?= $this->component(
        'empty-state',
        [
            'title' => $emptyTitle,
            'text' => $emptyText,
        ]
    ) ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table users-table">
            <thead>
            <tr>
                <th>Naam</th>
                <th>E-mailadres</th>
                <th>Rol</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($gebruikers as $gebruiker): ?>
                <tr class="<?= $gebruiker->isPending()
                    ? 'users-table__row--pending'
                    : '' ?>">
                    <td>
                        <div class="users-table__identity">
                            <span class="users-table__avatar">
                                <?= $this->escape(
                                    $gebruiker->initials()
                                ) ?>
                            </span>

                            <strong>
                                <?= $this->escape(
                                    $gebruiker->fullName()
                                ) ?>
                            </strong>
                        </div>
                    </td>

                    <td>
                        <?= $this->escape($gebruiker->email) ?>
                    </td>

                    <td>
                        <?= $this->escape($gebruiker->roleLabel()) ?>
                    </td>

                    <td>
                        <span class="user-status user-status--<?= $this->escape(
                            $gebruiker->statusCssClass()
                        ) ?>">
                            <?= $this->escape(
                                $gebruiker->statusLabel()
                            ) ?>
                        </span>
                    </td>

                    <td>
                        <div class="user-actions">
                            <?php if (
                                $quickApprove
                                && $gebruiker->isPending()
                            ): ?>
                                <?= $helpers->form->open(
                                    $helpers->url->to(
                                        '/users/'
                                        . $gebruiker->gebruikerId
                                        . '/approve'
                                    ),
                                    'POST',
                                    [
                                        'class' => 'user-actions__form',
                                    ]
                                ) ?>
                                    <button
                                        type="submit"
                                        class="btn btn-success users-table__action"
                                    >
                                        Goedkeuren
                                    </button>
                                <?= $helpers->form->close() ?>
                            <?php endif; ?>

                            <?php if (!$gebruiker->isPending()): ?>
                                <a
                                    href="<?= $this->escape(
                                        $helpers->url->to(
                                            '/users/'
                                            . $gebruiker->gebruikerId
                                        )
                                    ) ?>"
                                    class="btn btn-secondary users-table__action"
                                >
                                    Bekijken
                                </a>
                            <?php endif; ?>

                            <a
                                href="<?= $this->escape(
                                    $helpers->url->to(
                                        '/users/'
                                        . $gebruiker->gebruikerId
                                        . '/edit'
                                    )
                                ) ?>"
                                class="btn btn-primary users-table__action"
                            >
                                <?= $gebruiker->isPending()
                                    ? 'Beoordelen'
                                    : 'Wijzigen' ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
        .users-table__row--pending td {
            background: #fffbeb;
        }

        .users-table__identity {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .users-table__avatar {
            display: inline-flex;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: var(--color-primary, #b5121b);
            border-radius: 50%;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .user-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .user-actions__form {
            margin: 0;
        }

        .users-table__action {
            min-height: 34px;
            padding: 0.45rem 0.7rem;
            font-size: 0.82rem;
        }

        .user-status {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .user-status--active {
            color: #166534;
            background: #dcfce7;
        }

        .user-status--pending {
            color: #854d0e;
            background: #fef3c7;
        }

        .user-status--inactive {
            color: #475569;
            background: #e2e8f0;
        }
    </style>
<?php endif; ?>