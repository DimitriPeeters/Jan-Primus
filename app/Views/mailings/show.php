<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Mailing;
use App\Models\MailingRecipient;

/** @var ViewHelpers $helpers */
/** @var Mailing $mailing */
/** @var MailingRecipient[] $recipients */
/** @var array{active: bool, emails: string[]} $recipientRestriction */

$this->extend('layouts.app', ['title' => $title ?? $mailing->subject]);
?>

<?php $this->startSection('content'); ?>
<div class="mailing-show-page">
    <?= $this->component(
        'page-header',
        [
            'title' => $mailing->subject,
            'subtitle' => $mailing->typeLabel() . ' · ' . $mailing->displayCreatedAt(),
            'actions' => sprintf(
                '<a class="btn btn-secondary" href="%s">Terug naar mailings</a>',
                $this->escape($helpers->url->to('/mailings'))
            ),
        ]
    ) ?>

    <?php if ($recipientRestriction['active']): ?>
        <div class="alert alert-warning" role="status">
            Lokale mailtestmodus is actief; alleen expliciet toegestane
            testadressen kunnen worden afgeleverd.
        </div>
    <?php endif; ?>

    <div class="mailing-show-grid">
        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Overzicht</h2>
            </header>
            <div class="card__body">
                <dl class="mailing-details">
                    <div>
                        <dt>Status</dt>
                        <dd>
                            <span class="badge <?= $this->escape($mailing->statusCssClass()) ?>">
                                <?= $this->escape($mailing->statusLabel()) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Doelgroep</dt>
                        <dd><?= $this->escape(ucfirst(str_replace('_', ' ', $mailing->audienceType))) ?></dd>
                    </div>
                    <div>
                        <dt>Ontvangers</dt>
                        <dd><?= $mailing->recipientCount ?></dd>
                    </div>
                    <div>
                        <dt>Verzonden</dt>
                        <dd><?= $mailing->sentCount ?></dd>
                    </div>
                    <div>
                        <dt>Mislukt</dt>
                        <dd><?= $mailing->failedCount ?></dd>
                    </div>
                    <div>
                        <dt>Aangemaakt door</dt>
                        <dd><?= $this->escape($mailing->creatorName ?? 'Systeem') ?></dd>
                    </div>
                </dl>

                <?php if ($mailing->failedCount > 0): ?>
                    <form
                        method="post"
                        action="<?= $this->escape(
                            $helpers->url->to(
                                '/mailings/' . $mailing->mailingId . '/retry'
                            )
                        ) ?>"
                        class="mailing-retry-form"
                    >
                        <?= $helpers->csrf->field() ?>
                        <button type="submit" class="btn btn-warning">
                            Mislukte mails opnieuw inplannen
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <header class="card__header">
                <h2 class="card__title">Tekstversie</h2>
            </header>
            <div class="card__body">
                <pre class="mailing-preview"><?= $this->escape($mailing->text) ?></pre>
            </div>
        </section>
    </div>

    <section class="card">
        <header class="card__header">
            <h2 class="card__title">Afleveringen</h2>
        </header>
        <div class="card__body mailing-table-body">
            <?php if ($recipients === []): ?>
                <?= $this->component(
                    'empty-state',
                    [
                        'title' => 'Geen ontvangers',
                        'text' => 'Voor deze mailing werden geen geldige ontvangers gevonden.',
                    ]
                ) ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Naam</th>
                                <th>E-mailadres</th>
                                <th>Status</th>
                                <th>Pogingen</th>
                                <th>Verzonden</th>
                                <th>Fout</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recipients as $recipient): ?>
                                <tr>
                                    <td><?= $this->escape($recipient->name) ?></td>
                                    <td><?= $this->escape($recipient->email) ?></td>
                                    <td>
                                        <span class="badge <?= $this->escape($recipient->statusCssClass()) ?>">
                                            <?= $this->escape($recipient->statusLabel()) ?>
                                        </span>
                                    </td>
                                    <td><?= $recipient->attempts ?></td>
                                    <td><?= $this->escape($recipient->displaySentAt()) ?></td>
                                    <td class="mailing-error-cell">
                                        <?= $this->escape($recipient->error ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .mailing-show-page {
        display: grid;
        gap: 1.25rem;
    }

    .mailing-show-grid {
        display: grid;
        grid-template-columns: minmax(300px, 1fr) minmax(0, 1.4fr);
        gap: 1.25rem;
        align-items: start;
    }

    .mailing-details {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
        margin: 0;
    }

    .mailing-details div {
        padding: 0.75rem;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
        background: #f8fafc;
    }

    .mailing-details dt {
        color: var(--color-text-muted);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .mailing-details dd {
        margin: 0.3rem 0 0;
    }

    .mailing-retry-form {
        margin-top: 1rem;
    }

    .mailing-preview {
        margin: 0;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        font: inherit;
        line-height: 1.55;
    }

    .mailing-table-body {
        padding: 0;
    }

    .mailing-error-cell {
        min-width: 220px;
        max-width: 420px;
        color: #991b1b;
        overflow-wrap: anywhere;
    }

    @media (max-width: 900px) {
        .mailing-show-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 560px) {
        .mailing-details {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php $this->endSection(); ?>
