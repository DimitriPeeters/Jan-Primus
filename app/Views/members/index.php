<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;

/** @var ViewHelpers $helpers */
/** @var Member[] $leden */
/** @var string $zoekterm */
/** @var string|null $title */

$leden ??= [];
$zoekterm ??= '';

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Leden',
    ]
);
?>

<?php $this->startSection('styles'); ?>
<style>
    .members-page {
        display: grid;
        gap: 1.25rem;
    }

    .members-page__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .members-page__title {
        margin: 0 0 0.35rem;
        font-size: 1.75rem;
        line-height: 1.2;
    }

    .members-page__subtitle {
        margin: 0;
        color: var(--color-text-muted, #64748b);
    }

    .members-page__actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .members-search {
        display: flex;
        align-items: end;
        gap: 0.75rem;
        padding: 1rem;
    }

    .members-search__field {
        flex: 1;
        min-width: 0;
    }

    .members-search__label {
        display: block;
        margin-bottom: 0.4rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--color-text, #172033);
    }

    .members-search__input {
        width: 100%;
        height: 42px;
        padding: 0 0.85rem;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        background: #fff;
        font: inherit;
    }

    .members-search__input:focus {
        border-color: var(--color-primary, #b5121b);
        outline: 3px solid rgba(181, 18, 27, 0.12);
    }

    .members-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .members-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--color-border, #dfe4ec);
    }

    .members-card__title {
        margin: 0;
        font-size: 1rem;
    }

    .members-card__count {
        color: var(--color-text-muted, #64748b);
        font-size: 0.85rem;
    }

    .members-table-wrapper {
        overflow-x: auto;
    }

    .members-table {
        width: 100%;
        border-collapse: collapse;
    }

    .members-table th {
        padding: 0.8rem 0.9rem;
        background: #f7f9fc;
        color: #5c6b82;
        font-size: 0.75rem;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .members-table td {
        padding: 0.8rem 0.9rem;
        border-top: 1px solid var(--color-border, #e2e7ef);
        vertical-align: middle;
    }

    .members-table tbody tr:hover {
        background: #fafbfc;
    }

    .members-table__name {
        color: var(--color-text, #172033);
        font-weight: 600;
        text-decoration: none;
    }

    .members-table__name:hover {
        color: var(--color-primary, #b5121b);
        text-decoration: underline;
    }

    .members-table__muted {
        color: var(--color-text-muted, #64748b);
    }

    .members-table__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .member-status {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .member-status--active {
        color: #166534;
        background: #dcfce7;
    }

    .member-status--inactive {
        color: #991b1b;
        background: #fee2e2;
    }

    .members-empty {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--color-text-muted, #64748b);
    }

    @media (max-width: 760px) {
        .members-page__header {
            flex-direction: column;
        }

        .members-page__actions {
            width: 100%;
        }

        .members-page__actions .btn {
            width: 100%;
            justify-content: center;
        }

        .members-search {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('content'); ?>
<div class="members-page">

    <header class="members-page__header">
        <div>
            <h1 class="members-page__title">Leden</h1>

            <p class="members-page__subtitle">
                Bekijk en beheer de ledenprofielen die via de website werden geregistreerd.
            </p>
        </div>

    </header>

    <form
        method="get"
        action="<?= $this->escape(
            $helpers->url->to('/members')
        ) ?>"
        class="members-card members-search"
    >
        <div class="members-search__field">
            <label
                for="zoek"
                class="members-search__label"
            >
                Lid zoeken
            </label>

            <input
                type="search"
                id="zoek"
                name="zoek"
                value="<?= $this->escape($zoekterm) ?>"
                placeholder="Zoek op naam, e-mailadres of gemeente..."
                class="members-search__input"
            >
        </div>

        <button
            type="submit"
            class="btn btn-primary"
        >
            Zoeken
        </button>

        <?php if ($zoekterm !== ''): ?>
            <a
                href="<?= $this->escape(
                    $helpers->url->to('/members')
                ) ?>"
                class="btn btn-secondary"
            >
                Wissen
            </a>
        <?php endif; ?>
    </form>

    <section class="members-card">
        <header class="members-card__header">
            <h2 class="members-card__title">
                <?= $zoekterm === ''
                    ? 'Alle leden'
                    : 'Zoekresultaten' ?>
            </h2>

            <span class="members-card__count">
                <?= count($leden) ?>
                <?= count($leden) === 1 ? 'lid' : 'leden' ?>
            </span>
        </header>

        <?php if ($leden === []): ?>
            <div class="members-empty">
                <?php if ($zoekterm !== ''): ?>
                    Geen leden gevonden voor
                    <strong>
                        “<?= $this->escape($zoekterm) ?>”
                    </strong>.
                <?php else: ?>
                    Er zijn nog geen leden geregistreerd.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="members-table-wrapper">
                <table class="members-table">
                    <thead>
                    <tr>
                        <th scope="col">Naam</th>
                        <th scope="col">E-mailadres</th>
                        <th scope="col">Telefoon</th>
                        <th scope="col">Gemeente</th>
                        <th scope="col">Status</th>
                        <th scope="col">
                            <span class="sr-only">Acties</span>
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($leden as $lid): ?>
                        <?php
                        $showUrl = $helpers->url->to(
                            '/members/' . $lid->lidId
                        );

                        $editUrl = $helpers->url->to(
                            '/members/' . $lid->lidId . '/edit'
                        );

                        $location = trim(
                            implode(
                                ' ',
                                array_filter([
                                    $lid->postcode,
                                    $lid->gemeente,
                                ])
                            )
                        );
                        ?>

                        <tr>
                            <td>
                                <a
                                    href="<?= $this->escape($showUrl) ?>"
                                    class="members-table__name"
                                >
                                    <?= $this->escape(
                                        $lid->fullName()
                                    ) ?>
                                </a>
                            </td>

                            <td>
                                <?php if ($lid->hasEmail()): ?>
                                    <a
                                        href="mailto:<?= $this->escape(
                                            $lid->email
                                        ) ?>"
                                    >
                                        <?= $this->escape($lid->email) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="members-table__muted">
                                        —
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($lid->hasPhone()): ?>
                                    <a
                                        href="tel:<?= $this->escape(
                                            preg_replace(
                                                '/[^0-9+]/',
                                                '',
                                                (string) $lid->telefoon
                                            )
                                        ) ?>"
                                    >
                                        <?= $this->escape(
                                            $lid->telefoon
                                        ) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="members-table__muted">
                                        —
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($location !== ''): ?>
                                    <?= $this->escape($location) ?>
                                <?php else: ?>
                                    <span class="members-table__muted">
                                        —
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($lid->isActive()): ?>
                                    <span
                                        class="member-status member-status--active"
                                    >
                                        Actief
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="member-status member-status--inactive"
                                    >
                                        Inactief
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="members-table__actions">
                                    <a
                                        href="<?= $this->escape($showUrl) ?>"
                                        class="btn btn-secondary"
                                    >
                                        Bekijken
                                    </a>

                                    <a
                                        href="<?= $this->escape($editUrl) ?>"
                                        class="btn btn-secondary"
                                    >
                                        Wijzigen
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>
<?php $this->endSection(); ?>
