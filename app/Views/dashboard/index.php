<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var bool $isAdmin */
/** @var array<string, int> $statistics */
/** @var list<array<string, mixed>> $latestMembers */
/** @var list<array<string, mixed>> $pendingRegistrations */
/** @var list<array<string, mixed>> $pendingEventCancellations */
/** @var list<array<string, mixed>> $pendingEventRegistrations */
/** @var list<array<string, mixed>> $upcomingEvents */
/** @var list<array<string, mixed>> $openShifts */

$isAdmin ??= false;
$statistics ??= [];
$latestMembers ??= [];
$pendingRegistrations ??= [];
$pendingEventCancellations ??= [];
$pendingEventRegistrations ??= [];
$upcomingEvents ??= [];
$openShifts ??= [];

$formatDate = static fn(mixed $value): string =>
    BelgianDateTime::formatDate(
        trim((string) $value),
        '-'
    );

$formatDateTime = static fn(mixed $value): string =>
    BelgianDateTime::formatDateTime(
        trim((string) $value),
        '-'
    );

$this->extend('layouts.app', [
    'title' => 'Dashboard',
]);
?>

<?php $this->startSection('content'); ?>

<div class="dashboard">
    <header class="dashboard__header">
        <div>
            <h2 class="dashboard__title">
                Dashboard
            </h2>

            <p class="dashboard__description">
                <?= $isAdmin
                    ? 'Overzicht van leden, registraties en evenementen.'
                    : 'Welkom in AEFS Eventbeheer.' ?>
            </p>
        </div>

        <?php if ($isAdmin): ?>
            <a
                class="dashboard-manual"
                href="<?= $this->escape(
                    $helpers->asset->url(
                        'docs/aefs-v2-adminhandleiding.pdf'
                    )
                ) ?>"
                target="_blank"
                rel="noopener"
            >
                <span class="dashboard-manual__eyebrow">
                    Alfa-handleiding
                </span>

                <strong>Open adminhandleiding (PDF)</strong>

                <span>
                    Praktische uitleg over leden, events, shiften,
                    mailings, rapporten en instellingen.
                </span>
            </a>
        <?php endif; ?>
    </header>

    <div class="stats-grid">
        <?php if ($isAdmin): ?>
            <a
                class="stat-card stat-card--link"
                href="<?= $this->escape(
                    $helpers->url->to('/members')
                ) ?>"
            >
                <div class="stat-card__label">
                    Actieve leden
                </div>

                <div class="stat-card__value">
                    <?= $this->escape(
                        $statistics['members'] ?? 0
                    ) ?>
                </div>

                <div class="stat-card__hint">
                    Goedgekeurde ledenprofielen
                </div>
            </a>

            <a
                class="stat-card stat-card--link stat-card--warning"
                href="<?= $this->escape(
                    $helpers->url->to('/users')
                ) ?>"
            >
                <div class="stat-card__label">
                    Wachtende accounts
                </div>

                <div class="stat-card__value">
                    <?= $this->escape(
                        $statistics['pending'] ?? 0
                    ) ?>
                </div>

                <div class="stat-card__hint">
                    Te beoordelen accounts
                </div>
            </a>

            <a
                class="stat-card stat-card--link stat-card--warning"
                href="<?= $this->escape(
                    $helpers->url->to('/events')
                ) ?>"
            >
                <div class="stat-card__label">
                    Eventinschrijvingen
                </div>

                <div class="stat-card__value">
                    <?= $this->escape(
                        $statistics['eventRegistrations'] ?? 0
                    ) ?>
                </div>

                <div class="stat-card__hint">
                    Te beoordelen inschrijvingen
                </div>
            </a>

            <a
                class="stat-card stat-card--link"
                href="<?= $this->escape(
                    $helpers->url->to('/users')
                ) ?>"
            >
                <div class="stat-card__label">
                    Actieve gebruikers
                </div>

                <div class="stat-card__value">
                    <?= $this->escape(
                        $statistics['users'] ?? 0
                    ) ?>
                </div>

                <div class="stat-card__hint">
                    Actieve gekoppelde accounts
                </div>
            </a>

            <a
                class="stat-card stat-card--link stat-card--warning"
                href="<?= $this->escape(
                    $helpers->url->to('/events')
                ) ?>"
            >
                <div class="stat-card__label">
                    Annulatieaanvragen
                </div>

                <div class="stat-card__value">
                    <?= $this->escape(
                        $statistics['eventCancellations'] ?? 0
                    ) ?>
                </div>

                <div class="stat-card__hint">
                    Te verifiëren eventinschrijvingen
                </div>
            </a>
        <?php endif; ?>

        <a
            class="stat-card stat-card--link"
            href="<?= $this->escape(
                $helpers->url->to('/events')
            ) ?>"
        >
            <div class="stat-card__label">
                Komende evenementen
            </div>

            <div class="stat-card__value">
                <?= $this->escape(
                    $statistics['events'] ?? 0
                ) ?>
            </div>

            <div class="stat-card__hint">
                Vandaag en later
            </div>
        </a>

        <?php if (!$isAdmin): ?>
            <a
                class="stat-card stat-card--link"
                href="<?= $this->escape(
                    $helpers->url->to('/shifts')
                ) ?>"
            >
                <div class="stat-card__label">
                    Open shifts
                </div>

                <div class="stat-card__value">
                    <?= $this->escape(
                        $statistics['shifts'] ?? 0
                    ) ?>
                </div>

                <div class="stat-card__hint">
                    Beschikbare shiftmomenten
                </div>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
        <section class="dashboard-section dashboard-section--pending">
            <div class="dashboard-section__header">
                <div>
                    <h3 class="dashboard-section__title">
                        Wachtende eventinschrijvingen
                    </h3>

                    <p class="dashboard-section__description">
                        Nieuwe en hernieuwde inschrijvingen die nog moeten worden beoordeeld.
                    </p>
                </div>

                <a
                    class="dashboard-section__link"
                    href="<?= $this->escape(
                        $helpers->url->to('/events')
                    ) ?>"
                >
                    Naar evenementen
                </a>
            </div>

            <?php if ($pendingEventRegistrations === []): ?>
                <div class="dashboard-empty dashboard-empty--success">
                    <strong>Alles is verwerkt.</strong>

                    <span>
                        Er zijn momenteel geen eventinschrijvingen die op beoordeling wachten.
                    </span>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lid</th>
                                <th>Evenement</th>
                                <th>Ingeschreven</th>
                                <th>Actie</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($pendingEventRegistrations as $registration): ?>
                                <?php $eventId = (int) ($registration['event_id'] ?? 0); ?>

                                <tr>
                                    <td>
                                        <?= $this->escape(
                                            trim(
                                                (string) ($registration['voornaam'] ?? '')
                                                . ' '
                                                . (string) ($registration['achternaam'] ?? '')
                                            )
                                        ) ?>
                                    </td>
                                    <td><?= $this->escape($registration['event_titel'] ?? '-') ?></td>
                                    <td>
                                        <?= $this->escape(
                                            $formatDateTime(
                                                $registration['aangemeld_op'] ?? null
                                            )
                                        ) ?>
                                    </td>
                                    <td>
                                        <a
                                            class="btn btn-primary dashboard-action"
                                            href="<?= $this->escape(
                                                $helpers->url->to('/events/' . $eventId)
                                            ) ?>"
                                        >
                                            Beoordelen
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section dashboard-section--pending">
            <div class="dashboard-section__header">
                <div>
                    <h3 class="dashboard-section__title">
                        Openstaande annulatieaanvragen
                    </h3>

                    <p class="dashboard-section__description">
                        Leden met actieve shifttoewijzingen die hun deelname willen annuleren.
                    </p>
                </div>

                <a
                    class="dashboard-section__link"
                    href="<?= $this->escape(
                        $helpers->url->to('/events')
                    ) ?>"
                >
                    Naar evenementen
                </a>
            </div>

            <?php if ($pendingEventCancellations === []): ?>
                <div class="dashboard-empty dashboard-empty--success">
                    <strong>Geen openstaande annulaties.</strong>

                    <span>
                        Er zijn momenteel geen annulatieaanvragen die verificatie vereisen.
                    </span>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lid</th>
                                <th>Evenement</th>
                                <th>Aangevraagd</th>
                                <th>Actie</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($pendingEventCancellations as $cancellation): ?>
                                <?php $eventId = (int) ($cancellation['event_id'] ?? 0); ?>

                                <tr>
                                    <td>
                                        <?= $this->escape(
                                            trim(
                                                (string) ($cancellation['voornaam'] ?? '')
                                                . ' '
                                                . (string) ($cancellation['achternaam'] ?? '')
                                            )
                                        ) ?>
                                    </td>
                                    <td><?= $this->escape($cancellation['event_titel'] ?? '-') ?></td>
                                    <td>
                                        <?= $this->escape(
                                            $formatDateTime(
                                                $cancellation['annulatie_aangevraagd_op'] ?? null
                                            )
                                        ) ?>
                                    </td>
                                    <td>
                                        <a
                                            class="btn btn-primary dashboard-action"
                                            href="<?= $this->escape(
                                                $helpers->url->to('/events/' . $eventId)
                                            ) ?>"
                                        >
                                            Verifiëren
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section dashboard-section--pending">
            <div class="dashboard-section__header">
                <div>
                    <h3 class="dashboard-section__title">
                        Wachtende accounts
                    </h3>

                    <p class="dashboard-section__description">
                        Nieuwe accounts die nog moeten worden beoordeeld.
                    </p>
                </div>

                <a
                    class="dashboard-section__link"
                    href="<?= $this->escape(
                        $helpers->url->to('/users')
                    ) ?>"
                >
                    Naar gebruikersbeheer
                </a>
            </div>

            <?php if ($pendingRegistrations === []): ?>
                <div class="dashboard-empty dashboard-empty--success">
                    <strong>Alles is verwerkt.</strong>

                    <span>
                        Er zijn momenteel geen registraties die op
                        goedkeuring wachten.
                    </span>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Naam</th>
                                <th>E-mailadres</th>
                                <th>Status</th>
                                <th>Actie</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach (
                                $pendingRegistrations as $registration
                            ): ?>
                                <?php
                                $userId = (int) (
                                    $registration['gebruiker_id'] ?? 0
                                );
                                ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= $this->escape(
                                                trim(
                                                    (string) (
                                                        $registration['voornaam']
                                                        ?? ''
                                                    )
                                                    . ' '
                                                    . (string) (
                                                        $registration['achternaam']
                                                        ?? ''
                                                    )
                                                )
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= $this->escape(
                                            $registration['email'] ?? '-'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="dashboard-status
                                                dashboard-status--pending"
                                        >
                                            Wacht op goedkeuring
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            class="btn btn-primary
                                                dashboard-action"
                                            href="<?= $this->escape(
                                                $helpers->url->to(
                                                    '/users/'
                                                    . $userId
                                                    . '/edit'
                                                )
                                            ) ?>"
                                        >
                                            Beoordelen
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section">
            <div class="dashboard-section__header">
                <div>
                    <h3 class="dashboard-section__title">
                        Laatste goedgekeurde leden
                    </h3>

                    <p class="dashboard-section__description">
                        Alleen actieve leden met een goedgekeurd account.
                    </p>
                </div>

                <a
                    class="dashboard-section__link"
                    href="<?= $this->escape(
                        $helpers->url->to('/members')
                    ) ?>"
                >
                    Alle leden
                </a>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Naam</th>
                            <th>Gemeente</th>
                            <th>Actie</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($latestMembers === []): ?>
                            <tr>
                                <td colspan="3">
                                    Geen goedgekeurde leden gevonden.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestMembers as $member): ?>
                                <?php
                                $memberId = (int) (
                                    $member['lid_id'] ?? 0
                                );
                                ?>

                                <tr>
                                    <td>
                                        <?= $this->escape(
                                            trim(
                                                (string) (
                                                    $member['voornaam'] ?? ''
                                                )
                                                . ' '
                                                . (string) (
                                                    $member['achternaam'] ?? ''
                                                )
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $this->escape(
                                            $member['gemeente'] ?? '-'
                                        ) ?>
                                    </td>

                                    <td>
                                        <a
                                            class="btn btn-secondary
                                                dashboard-action"
                                            href="<?= $this->escape(
                                                $helpers->url->to(
                                                    '/members/' . $memberId
                                                )
                                            ) ?>"
                                        >
                                            Bekijken
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="dashboard-section">
        <div class="dashboard-section__header">
            <div>
                <h3 class="dashboard-section__title">
                    Komende evenementen
                </h3>

                <p class="dashboard-section__description">
                    Evenementen die vandaag plaatsvinden of nog moeten
                    beginnen.
                </p>
            </div>

            <a
                class="dashboard-section__link"
                href="<?= $this->escape(
                    $helpers->url->to('/events')
                ) ?>"
            >
                Alle evenementen
            </a>
        </div>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Evenement</th>
                        <th>Datum</th>
                        <th>Locatie</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($upcomingEvents === []): ?>
                        <tr>
                            <td colspan="3">
                                Geen komende evenementen gepland.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <?php
                            $startDate = $formatDate(
                                $event['startdatum'] ?? null
                            );

                            $endDate = $formatDate(
                                $event['einddatum'] ?? null
                            );

                            $dateLabel = $endDate !== '-'
                                && $endDate !== $startDate
                                ? $startDate . ' – ' . $endDate
                                : $startDate;
                            ?>

                            <tr>
                                <td>
                                    <?= $this->escape(
                                        $event['titel'] ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $this->escape($dateLabel) ?>
                                </td>

                                <td>
                                    <?= $this->escape(
                                        $event['locatie'] ?? '-'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php $this->endSection(); ?>
