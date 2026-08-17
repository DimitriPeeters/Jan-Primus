<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;
use App\Models\MemberGroup;

/** @var ViewHelpers $helpers */
/** @var MemberGroup[] $groepen */
/** @var MemberGroup|null $geselecteerdeGroep */
/** @var Member[] $leden */
/** @var int[] $geselecteerdeLidIds */
/** @var array<int, MemberGroup> $groepPerLid */
/** @var string|null $title */

$groepen ??= [];
$geselecteerdeGroep ??= null;
$leden ??= [];
$geselecteerdeLidIds ??= [];
$groepPerLid ??= [];

$oldInput = $helpers->old->all();
$oldName = (string) ($oldInput['naam'] ?? '');
$oldDescription = (string) ($oldInput['beschrijving'] ?? '');

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Ledengroepen',
    ]
);
?>

<?php $this->startSection('styles'); ?>
<style>
    .member-groups-page {
        display: grid;
        gap: 1.25rem;
    }

    .member-groups-page__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .member-groups-page__title {
        margin: 0 0 0.35rem;
        font-size: 1.75rem;
        line-height: 1.2;
    }

    .member-groups-page__subtitle,
    .member-groups-card__description {
        margin: 0;
        color: var(--color-text-muted, #64748b);
    }

    .member-groups-layout {
        display: grid;
        grid-template-columns: minmax(280px, 0.7fr) minmax(0, 1.3fr);
        gap: 1.25rem;
        align-items: start;
    }

    .member-groups-sidebar {
        display: grid;
        gap: 1.25rem;
    }

    .member-groups-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .member-groups-card__header {
        padding: 1rem;
        border-bottom: 1px solid var(--color-border, #e2e7ef);
        background: #f8fafc;
    }

    .member-groups-card__title {
        margin: 0 0 0.25rem;
        font-size: 1rem;
    }

    .member-groups-card__body {
        padding: 1rem;
    }

    .member-group-form {
        display: grid;
        gap: 1rem;
    }

    .member-group-form__field {
        display: grid;
        gap: 0.4rem;
    }

    .member-group-form__label {
        font-size: 0.85rem;
        font-weight: 700;
    }

    .member-group-form__control {
        width: 100%;
        min-height: 42px;
        padding: 0.7rem 0.8rem;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        background: #fff;
        font: inherit;
    }

    textarea.member-group-form__control {
        min-height: 96px;
        resize: vertical;
    }

    .member-group-form__control:focus {
        border-color: var(--color-primary, #b5121b);
        outline: 3px solid rgba(181, 18, 27, 0.12);
    }

    .member-group-list {
        display: grid;
        gap: 0.65rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .member-group-list__link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 1px solid var(--color-border, #e2e7ef);
        border-radius: 8px;
        color: var(--color-text, #172033);
        text-decoration: none;
    }

    .member-group-list__link:hover,
    .member-group-list__link--active {
        border-color: var(--color-primary, #b5121b);
        background: rgba(181, 18, 27, 0.05);
    }

    .member-group-list__count {
        display: inline-flex;
        min-width: 28px;
        min-height: 24px;
        align-items: center;
        justify-content: center;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
        background: #e2e8f0;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .member-group-members {
        display: grid;
        gap: 0.75rem;
        max-height: 620px;
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .member-group-member {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 1px solid var(--color-border, #e2e7ef);
        border-radius: 8px;
        cursor: pointer;
    }

    .member-group-member:hover {
        background: #fafbfc;
    }

    .member-group-member--unavailable {
        cursor: not-allowed;
        background: #f8fafc;
        opacity: 0.65;
    }

    .member-group-member[hidden] {
        display: none;
    }

    .member-group-member__input {
        width: 18px;
        height: 18px;
        margin-top: 0.15rem;
        accent-color: var(--color-primary, #b5121b);
    }

    .member-group-member__details {
        display: grid;
        gap: 0.15rem;
        min-width: 0;
    }

    .member-group-member__name {
        font-weight: 700;
    }

    .member-group-member__meta {
        color: var(--color-text-muted, #64748b);
        font-size: 0.82rem;
        overflow-wrap: anywhere;
    }

    .member-groups-empty {
        padding: 1rem;
        border: 1px dashed var(--color-border, #d8dee9);
        border-radius: 8px;
        color: var(--color-text-muted, #64748b);
        text-align: center;
    }

    .member-group-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 0.25rem;
    }

    @media (max-width: 900px) {
        .member-groups-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 680px) {
        .member-groups-page__header {
            align-items: stretch;
            flex-direction: column;
        }

        .member-groups-page__header .btn,
        .member-group-actions .btn {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('content'); ?>
<div class="member-groups-page">
    <header class="member-groups-page__header">
        <div>
            <h1 class="member-groups-page__title">Ledengroepen</h1>

            <p class="member-groups-page__subtitle">
                Maak groepen aan en bepaal welke leden ervan deel uitmaken.
            </p>
        </div>

        <a
            class="btn btn-secondary"
            href="<?= $this->escape($helpers->url->to('/members')) ?>"
        >
            Terug naar leden
        </a>
    </header>

    <div class="member-groups-layout">
        <aside class="member-groups-sidebar">
            <section class="member-groups-card">
                <header class="member-groups-card__header">
                    <h2 class="member-groups-card__title">
                        Nieuwe groep
                    </h2>

                    <p class="member-groups-card__description">
                        Gebruik een herkenbare naam voor mailing en rapportage.
                    </p>
                </header>

                <div class="member-groups-card__body">
                    <form
                        class="member-group-form"
                        method="post"
                        action="<?= $this->escape(
                            $helpers->url->to('/members/groups/create')
                        ) ?>"
                    >
                        <?= $helpers->csrf->field() ?>

                        <label class="member-group-form__field">
                            <span class="member-group-form__label">
                                Naam *
                            </span>

                            <input
                                class="member-group-form__control"
                                type="text"
                                name="naam"
                                value="<?= $this->escape($oldName) ?>"
                                maxlength="100"
                                required
                            >
                        </label>

                        <label class="member-group-form__field">
                            <span class="member-group-form__label">
                                Beschrijving
                            </span>

                            <textarea
                                class="member-group-form__control"
                                name="beschrijving"
                                maxlength="5000"
                            ><?= $this->escape($oldDescription) ?></textarea>
                        </label>

                        <button class="btn btn-success" type="submit">
                            Groep aanmaken
                        </button>
                    </form>
                </div>
            </section>

            <section class="member-groups-card">
                <header class="member-groups-card__header">
                    <h2 class="member-groups-card__title">
                        Bestaande groepen
                    </h2>

                    <p class="member-groups-card__description">
                        <?= count($groepen) ?> groep<?= count($groepen) === 1 ? '' : 'en' ?>
                    </p>
                </header>

                <div class="member-groups-card__body">
                    <?php if ($groepen === []): ?>
                        <div class="member-groups-empty">
                            Er zijn nog geen groepen aangemaakt.
                        </div>
                    <?php else: ?>
                        <ul class="member-group-list">
                            <?php foreach ($groepen as $groep): ?>
                                <?php
                                $isSelected = $geselecteerdeGroep !== null
                                    && $geselecteerdeGroep->groepId === $groep->groepId;
                                ?>

                                <li>
                                    <a
                                        class="member-group-list__link<?= $isSelected
                                            ? ' member-group-list__link--active'
                                            : '' ?>"
                                        href="<?= $this->escape(
                                            $helpers->url->to(
                                                '/members/groups?groep=' . $groep->groepId
                                            )
                                        ) ?>"
                                    >
                                        <span><?= $this->escape($groep->naam) ?></span>

                                        <span class="member-group-list__count">
                                            <?= $this->escape((string) $groep->ledenAantal) ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </aside>

        <section class="member-groups-card">
            <?php if ($geselecteerdeGroep === null): ?>
                <div class="member-groups-card__body">
                    <div class="member-groups-empty">
                        Maak eerst een groep aan om leden toe te wijzen.
                    </div>
                </div>
            <?php else: ?>
                <header class="member-groups-card__header">
                    <h2 class="member-groups-card__title">
                        <?= $this->escape($geselecteerdeGroep->naam) ?>
                    </h2>

                    <p class="member-groups-card__description">
                        <?= $geselecteerdeGroep->beschrijving !== null
                            ? $this->escape($geselecteerdeGroep->beschrijving)
                            : 'Selecteer de leden die tot deze groep behoren.' ?>
                        Een lid kan slechts tot één groep behoren.
                    </p>
                </header>

                <div class="member-groups-card__body">
                    <form
                        class="member-group-form"
                        method="post"
                        action="<?= $this->escape(
                            $helpers->url->to(
                                '/members/groups/'
                                . $geselecteerdeGroep->groepId
                                . '/members'
                            )
                        ) ?>"
                    >
                        <?= $helpers->csrf->field() ?>

                        <label class="member-group-form__field">
                            <span class="member-group-form__label">
                                Leden zoeken
                            </span>

                            <input
                                class="member-group-form__control"
                                type="search"
                                id="member-group-filter"
                                placeholder="Zoek op naam, e-mailadres of gemeente..."
                                autocomplete="off"
                            >
                        </label>

                        <?php if ($leden === []): ?>
                            <div class="member-groups-empty">
                                Er zijn geen leden om toe te wijzen.
                            </div>
                        <?php else: ?>
                            <div
                                class="member-group-members"
                                id="member-group-members"
                            >
                                <?php foreach ($leden as $lid): ?>
                                    <?php
                                    $assignedGroup = $groepPerLid[$lid->lidId]
                                        ?? null;
                                    $assignedElsewhere = $assignedGroup !== null
                                        && $assignedGroup->groepId
                                            !== $geselecteerdeGroep->groepId;
                                    $searchValue = strtolower(
                                        trim(
                                            $lid->fullName()
                                            . ' '
                                            . ($lid->email ?? '')
                                            . ' '
                                            . ($lid->gemeente ?? '')
                                            . ' '
                                            . ($assignedGroup?->naam ?? '')
                                        )
                                    );
                                    ?>

                                    <label
                                        class="member-group-member<?= $assignedElsewhere
                                            ? ' member-group-member--unavailable'
                                            : '' ?>"
                                        data-member-search="<?= $this->escape($searchValue) ?>"
                                    >
                                        <input
                                            class="member-group-member__input"
                                            type="checkbox"
                                            name="lid_ids[]"
                                            value="<?= $this->escape((string) $lid->lidId) ?>"
                                            <?= in_array(
                                                $lid->lidId,
                                                $geselecteerdeLidIds,
                                                true
                                            ) ? ' checked' : '' ?>
                                            <?= $assignedElsewhere ? ' disabled' : '' ?>
                                        >

                                        <span class="member-group-member__details">
                                            <span class="member-group-member__name">
                                                <?= $this->escape($lid->fullName()) ?>
                                            </span>

                                            <span class="member-group-member__meta">
                                                <?= $this->escape(
                                                    implode(
                                                        ' · ',
                                                        array_filter([
                                                            $lid->email,
                                                            $lid->gemeente,
                                                            $lid->isActive()
                                                                ? 'Actief'
                                                                : 'Inactief',
                                                            $assignedElsewhere
                                                                ? 'Reeds lid van ' . $assignedGroup->naam
                                                                : null,
                                                        ])
                                                    )
                                                ) ?>
                                            </span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="member-group-actions">
                            <button class="btn btn-success" type="submit">
                                Groepsleden opslaan
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('scripts'); ?>
<script>
    (() => {
        const filter = document.getElementById('member-group-filter');
        const list = document.getElementById('member-group-members');

        if (!(filter instanceof HTMLInputElement) || !(list instanceof HTMLElement)) {
            return;
        }

        const members = Array.from(
            list.querySelectorAll('[data-member-search]')
        );

        filter.addEventListener('input', () => {
            const query = filter.value.trim().toLocaleLowerCase('nl-BE');

            members.forEach((member) => {
                const searchable = (member.dataset.memberSearch ?? '')
                    .toLocaleLowerCase('nl-BE');

                member.hidden = query !== '' && !searchable.includes(query);
            });
        });
    })();
</script>
<?php $this->endSection(); ?>
