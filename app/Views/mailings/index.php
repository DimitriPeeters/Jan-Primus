<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Mailing;

/** @var ViewHelpers $helpers */
/** @var Mailing[] $mailings */
/** @var array{queued: int, sent: int, failed: int, total: int} $totals */
/** @var bool $mailConfigured */
/** @var string $smtpHost */
/** @var array{active: bool, emails: string[]} $recipientRestriction */

$this->extend('layouts.app', ['title' => $title ?? 'Mailings']);
?>

<?php $this->startSection('content'); ?>
<div class="mailings-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Mailings',
            'subtitle' => 'Automatische en manuele mails met individuele aflevering en opvolging.',
            'actions' => sprintf(
                '<a class="btn btn-primary" href="%s">Nieuwe mailing</a>',
                $this->escape($helpers->url->to('/mailings/create'))
            ),
        ]
    ) ?>

    <?php if ($recipientRestriction['active']): ?>
        <div class="alert alert-warning" role="status">
            <strong>Lokale mailtestmodus actief.</strong>
            Automatische en manuele mailings worden uitsluitend ingepland voor:
            <?= $this->escape(implode(', ', $recipientRestriction['emails'])) ?>.
        </div>
    <?php endif; ?>

    <?php if (!$mailConfigured): ?>
        <div class="alert alert-warning" role="alert">
            SMTP is nog niet volledig geconfigureerd. Nieuwe mails mogen al in de wachtrij worden geplaatst,
            maar de worker verstuurt ze pas nadat <code>config/local/mail.php</code> is ingevuld en ingeschakeld.
        </div>
    <?php else: ?>
        <div class="alert alert-success" role="status">
            SMTP-verzending is geconfigureerd via <?= $this->escape($smtpHost) ?>.
        </div>
    <?php endif; ?>

    <div class="mailing-stats">
        <article class="mailing-stat">
            <span>In wachtrij</span>
            <strong><?= $totals['queued'] ?></strong>
        </article>
        <article class="mailing-stat">
            <span>Verzonden</span>
            <strong><?= $totals['sent'] ?></strong>
        </article>
        <article class="mailing-stat">
            <span>Mislukt</span>
            <strong><?= $totals['failed'] ?></strong>
        </article>
        <article class="mailing-stat">
            <span>Totaal afleveringen</span>
            <strong><?= $totals['total'] ?></strong>
        </article>
    </div>

    <section class="card">
        <header class="card__header">
            <h2 class="card__title">Mailhistoriek</h2>
        </header>

        <div class="card__body mailing-table-body">
            <?php if ($mailings === []): ?>
                <?= $this->component(
                    'empty-state',
                    [
                        'title' => 'Nog geen mailings',
                        'text' => 'Publicaties, beslissingen en manuele mailings verschijnen hier zodra ze worden ingepland.',
                    ]
                ) ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Aangemaakt</th>
                                <th>Type</th>
                                <th>Onderwerp</th>
                                <th>Status</th>
                                <th>Voortgang</th>
                                <th>Actie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mailings as $mailing): ?>
                                <tr>
                                    <td><?= $this->escape($mailing->displayCreatedAt()) ?></td>
                                    <td><?= $this->escape($mailing->typeLabel()) ?></td>
                                    <td>
                                        <strong><?= $this->escape($mailing->subject) ?></strong>
                                        <?php if ($mailing->eventTitle !== null): ?>
                                            <small class="mailing-muted">
                                                <?= $this->escape($mailing->eventTitle) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $this->escape($mailing->statusCssClass()) ?>">
                                            <?= $this->escape($mailing->statusLabel()) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $this->escape($mailing->progressLabel()) ?>
                                        <?php if ($mailing->failedCount > 0): ?>
                                            <small class="mailing-error">
                                                <?= $mailing->failedCount ?> mislukt
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a
                                            class="btn btn-secondary"
                                            href="<?= $this->escape(
                                                $helpers->url->to(
                                                    '/mailings/' . $mailing->mailingId
                                                )
                                            ) ?>"
                                        >
                                            Bekijken
                                        </a>
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
    .mailings-page {
        display: grid;
        gap: 1.25rem;
    }

    .mailing-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .mailing-stat {
        display: grid;
        gap: 0.35rem;
        padding: 1.1rem;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
        background: #fff;
    }

    .mailing-stat span,
    .mailing-muted,
    .mailing-error {
        display: block;
        color: var(--color-text-muted);
        font-size: 0.82rem;
    }

    .mailing-stat strong {
        color: var(--color-primary);
        font-size: 1.8rem;
    }

    .mailing-error {
        color: #b91c1c;
    }

    .mailing-table-body {
        padding: 0;
    }

    @media (max-width: 950px) {
        .mailing-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 560px) {
        .mailing-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php $this->endSection(); ?>
