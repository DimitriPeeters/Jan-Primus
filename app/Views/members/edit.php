<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;

/** @var ViewHelpers $helpers */
/** @var Member $lid */
/** @var array<string, mixed> $errors */
/** @var string|null $title */

$errors ??= [];

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? $lid->fullName(),
    ]
);

$showUrl = $helpers->url->to(
    '/members/' . $lid->lidId
);

$updateUrl = $helpers->url->to(
    '/members/' . $lid->lidId . '/update'
);
?>

<?php $this->startSection('content'); ?>
<div class="member-edit-page">
    <header class="member-edit-page__header">
        <div>
            <h1 class="member-edit-page__title">
                <?= $this->escape($lid->fullName()) ?>
            </h1>

            <p class="member-edit-page__subtitle">
                Lidgegevens wijzigen
            </p>
        </div>

        <a
            href="<?= $this->escape($showUrl) ?>"
            class="btn btn-secondary"
        >
            Annuleren
        </a>
    </header>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <strong>De gegevens konden niet worden opgeslagen.</strong>

            <ul>
                <?php foreach ($errors as $error): ?>
                    <?php if (is_array($error)): ?>
                        <?php foreach ($error as $message): ?>
                            <li>
                                <?= $this->escape((string) $message) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>
                            <?= $this->escape((string) $error) ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?= $this->escape($updateUrl) ?>"
        novalidate
    >
        <?= $this->component(
            'members/form',
            [
                'lid' => $lid,
            ]
        ) ?>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .member-edit-page {
        display: grid;
        gap: 1.25rem;
    }

    .member-edit-page__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .member-edit-page__title {
        margin: 0 0 0.35rem;
        font-size: 1.75rem;
        line-height: 1.2;
    }

    .member-edit-page__subtitle {
        margin: 0;
        color: var(--color-text-muted, #64748b);
    }

    @media (max-width: 680px) {
        .member-edit-page__header {
            align-items: stretch;
            flex-direction: column;
        }

        .member-edit-page__header .btn {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>