<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\ShiftType;

/** @var ViewHelpers $helpers */
/** @var array<string, string> $settings */
/** @var array<int, array{type: ShiftType, shift_count: int}> $shiftTypes */
/** @var array<string, mixed> $status */

$this->extend('layouts.app', ['title' => $title ?? 'Instellingen']);


$mail = $status['mail'];
$system = $status['system'];
?>

<?php $this->startSection('content'); ?>
<div class="settings-page">
    <?= $this->component(
        'page-header',
        [
            'title' => 'Instellingen',
            'subtitle' => 'Beheer centrale standaarden en controleer de technische gereedheid van Ledenbeheer.',
        ]
    ) ?>

    <div class="settings-grid">
        <section class="card" id="general-settings">
            <header class="card__header">
                <div>
                    <h2 class="card__title">Organisatie en standaarden</h2>
                    <p class="settings-card-subtitle">
                        Deze waarden worden door nieuwe dossiers, rapporten en uitgaande mails gebruikt.
                    </p>
                </div>
            </header>

            <form
                method="post"
                action="<?= $this->escape($helpers->url->to('/settings/update')) ?>"
            >
                <?= $helpers->csrf->field() ?>

                <div class="card__body settings-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="application_name">Platformnaam</label>
                        <input
                            class="form-control"
                            id="application_name"
                            name="application_name"
                            type="text"
                            maxlength="150"
                            value="<?= $this->escape($settings['application_name']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="organization_name">Organisatienaam</label>
                        <input
                            class="form-control"
                            id="organization_name"
                            name="organization_name"
                            type="text"
                            maxlength="150"
                            value="<?= $this->escape($settings['organization_name']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mail_from_name">Naam van de mailafzender</label>
                        <input
                            class="form-control"
                            id="mail_from_name"
                            name="mail_from_name"
                            type="text"
                            maxlength="150"
                            value="<?= $this->escape($settings['mail_from_name']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mail_reply_to_name">Naam voor antwoorden</label>
                        <input
                            class="form-control"
                            id="mail_reply_to_name"
                            name="mail_reply_to_name"
                            type="text"
                            maxlength="150"
                            value="<?= $this->escape($settings['mail_reply_to_name']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group settings-form-field--full">
                        <label class="form-label" for="mail_reply_to_address">Antwoordadres</label>
                        <input
                            class="form-control"
                            id="mail_reply_to_address"
                            name="mail_reply_to_address"
                            type="email"
                            maxlength="255"
                            value="<?= $this->escape($settings['mail_reply_to_address']) ?>"
                            placeholder="Leeg = technisch geconfigureerd antwoordadres"
                        >
                        <small class="settings-help">
                            Het technische afzenderadres en SMTP-account blijven bewust buiten de webinterface.
                        </small>
                    </div>

                </div>

                <footer class="card__footer settings-actions">
                    <button class="btn btn-success" type="submit">
                        Instellingen opslaan
                    </button>
                </footer>
            </form>
        </section>

        <div class="settings-status-stack">
            <section class="card">
                <header class="card__header">
                    <h2 class="card__title">Mailinfrastructuur</h2>
                </header>
                <div class="card__body settings-status-list">
                    <div>
                        <span>SMTP</span>
                        <strong class="settings-status settings-status--<?= $mail['configured'] ? 'ok' : 'warning' ?>">
                            <?= $mail['configured'] ? 'Gereed' : 'Onvolledig' ?>
                        </strong>
                    </div>
                    <div>
                        <span>Server</span>
                        <strong><?= $this->escape($mail['host'] !== '' ? $mail['host'] : 'Niet ingesteld') ?></strong>
                    </div>
                    <div>
                        <span>Afzenderadres</span>
                        <strong><?= $this->escape(
                            $mail['from_address'] !== ''
                                ? $mail['from_address']
                                : 'Niet ingesteld'
                        ) ?></strong>
                    </div>
                    <div>
                        <span>Applicatie-URL</span>
                        <strong><?= $this->escape(
                            $mail['application_url'] !== ''
                                ? $mail['application_url']
                                : 'Niet ingesteld'
                        ) ?></strong>
                    </div>
                    <div>
                        <span>Testbeperking</span>
                        <strong class="settings-status settings-status--<?= $mail['restriction']['active'] ? 'warning' : 'ok' ?>">
                            <?= $mail['restriction']['active']
                                ? count($mail['restriction']['emails']) . ' toegelaten adres(s)'
                                : 'Uitgeschakeld' ?>
                        </strong>
                    </div>
                    <div>
                        <span>Wachtrij</span>
                        <strong><?= (int) $mail['totals']['queued'] ?> te verwerken</strong>
                    </div>
                    <div>
                        <span>Workerprofiel</span>
                        <strong>
                            <?= (int) $mail['batch_size'] ?> per run ·
                            <?= (int) $mail['max_attempts'] ?> pogingen<br>
                            <?= (int) $mail['rate_limit_per_minute'] ?> per minuut ·
                            <?= (int) $mail['rate_limit_per_hour'] ?> per uur
                        </strong>
                    </div>
                    <div>
                        <span>Achtergrondworker</span>
                        <strong
                            class="settings-status settings-status--<?= $mail['scheduler']['configured']
                                ? 'ok'
                                : 'warning' ?>"
                        >
                            <?php if ($mail['scheduler']['configured']): ?>
                                Scheduleringang gereed
                            <?php elseif ($mail['scheduler']['enabled']): ?>
                                Configuratie ongeldig
                            <?php else: ?>
                                Niet geconfigureerd
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
                <footer class="card__footer settings-note">
                    <?php if ($mail['scheduler']['configured']): ?>
                        De beveiligde HTTPS-ingang is gereed. De externe scheduler moet daarnaast actief blijven
                        om de wachtrij na het sluiten van Ledenbeheer te verwerken.
                    <?php else: ?>
                        De webpagina start zelf geen achtergrondworker. Een lokale taak, server-cron of beveiligde
                        externe scheduler moet actief blijven om de wachtrij te verwerken.
                    <?php endif; ?>
                </footer>
            </section>

            <section class="card">
                <header class="card__header">
                    <h2 class="card__title">Systeem en beveiliging</h2>
                </header>
                <div class="card__body settings-status-list">
                    <div>
                        <span>Omgeving</span>
                        <strong><?= $this->escape($system['environment']) ?></strong>
                    </div>
                    <div>
                        <span>Tijdzone</span>
                        <strong><?= $this->escape($system['timezone']) ?></strong>
                    </div>
                    <div>
                        <span>PHP</span>
                        <strong><?= $this->escape($system['php_version']) ?></strong>
                    </div>
                    <div>
                        <span>Encryptiesleutel</span>
                        <strong class="settings-status settings-status--<?= $system['app_key_configured'] ? 'ok' : 'danger' ?>">
                            <?= $system['app_key_configured'] ? 'Geconfigureerd' : 'Ontbreekt' ?>
                        </strong>
                    </div>
                </div>
                <footer class="card__footer settings-note">
                    De encryptiesleutel, databasegegevens en SMTP-wachtwoorden zijn alleen lokaal configureerbaar
                    en worden hier nooit getoond of aangepast.
                </footer>
            </section>
        </div>
    </div>

    <section class="card" id="shift-types">
        <header class="card__header">
            <div>
                <h2 class="card__title">Shiftfuncties</h2>
                <p class="settings-card-subtitle">
                    Functies met historie worden gedeactiveerd in plaats van verwijderd.
                </p>
            </div>
        </header>

        <div class="card__body settings-types">
            <form
                class="settings-type-card settings-type-card--new"
                method="post"
                action="<?= $this->escape(
                    $helpers->url->to('/settings/shift-types/store')
                ) ?>"
            >
                <?= $helpers->csrf->field() ?>
                <div class="settings-type-heading">
                    <strong>Nieuwe shiftfunctie</strong>
                    <span class="badge badge-info">Nieuw</span>
                </div>
                <div class="settings-type-grid">
                    <div class="form-group">
                        <label class="form-label" for="new-type-name">Naam</label>
                        <input class="form-control" id="new-type-name" name="naam" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new-type-color">Kleur</label>
                        <input class="form-control settings-color" id="new-type-color" name="kleur" type="color" value="#1E3A8A" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new-type-icon">Icoonnaam</label>
                        <input class="form-control" id="new-type-icon" name="icoon" maxlength="50" placeholder="bijv. users">
                    </div>
                    <div class="form-group settings-type-description">
                        <label class="form-label" for="new-type-description">Omschrijving</label>
                        <input class="form-control" id="new-type-description" name="omschrijving" maxlength="1000">
                    </div>
                </div>
                <input type="hidden" name="actief" value="1">
                <button class="btn btn-primary" type="submit">Functie toevoegen</button>
            </form>

            <?php foreach ($shiftTypes as $row): ?>
                <?php $type = $row['type']; ?>
                <form
                    class="settings-type-card"
                    id="shift-type-<?= $type->typeId ?>"
                    method="post"
                    action="<?= $this->escape(
                        $helpers->url->to(
                            '/settings/shift-types/' . $type->typeId . '/update'
                        )
                    ) ?>"
                >
                    <?= $helpers->csrf->field() ?>
                    <div class="settings-type-heading">
                        <div>
                            <strong><?= $this->escape($type->naam) ?></strong>
                            <small><?= (int) $row['shift_count'] ?> gekoppelde shift(s)</small>
                        </div>
                        <span class="badge <?= $type->isActief() ? 'badge-success' : 'badge-warning' ?>">
                            <?= $type->isActief() ? 'Actief' : 'Inactief' ?>
                        </span>
                    </div>

                    <div class="settings-type-grid">
                        <div class="form-group">
                            <label class="form-label" for="type-name-<?= $type->typeId ?>">Naam</label>
                            <input
                                class="form-control"
                                id="type-name-<?= $type->typeId ?>"
                                name="naam"
                                maxlength="100"
                                value="<?= $this->escape($type->naam) ?>"
                                <?= $type->isDefault() ? 'readonly' : '' ?>
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="type-color-<?= $type->typeId ?>">Kleur</label>
                            <input
                                class="form-control settings-color"
                                id="type-color-<?= $type->typeId ?>"
                                name="kleur"
                                type="color"
                                value="<?= $this->escape($type->kleur) ?>"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="type-icon-<?= $type->typeId ?>">Icoonnaam</label>
                            <input
                                class="form-control"
                                id="type-icon-<?= $type->typeId ?>"
                                name="icoon"
                                maxlength="50"
                                value="<?= $this->escape($type->icoon ?? '') ?>"
                            >
                        </div>
                        <div class="form-group settings-type-description">
                            <label class="form-label" for="type-description-<?= $type->typeId ?>">Omschrijving</label>
                            <input
                                class="form-control"
                                id="type-description-<?= $type->typeId ?>"
                                name="omschrijving"
                                maxlength="1000"
                                value="<?= $this->escape($type->omschrijving ?? '') ?>"
                            >
                        </div>
                    </div>

                    <div class="settings-type-actions">
                        <?php if ($type->isDefault()): ?>
                            <input type="hidden" name="actief" value="1">
                            <span class="settings-help">Steward is de verplichte standaardfunctie.</span>
                        <?php else: ?>
                            <input type="hidden" name="actief" value="0">
                            <label class="settings-inline-checkbox">
                                <input
                                    name="actief"
                                    type="checkbox"
                                    value="1"
                                    <?= $type->isActief() ? 'checked' : '' ?>
                                >
                                Actief
                            </label>
                        <?php endif; ?>

                        <button class="btn btn-secondary" type="submit">Opslaan</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .settings-page,
    .settings-status-stack,
    .settings-types {
        display: grid;
        gap: 1.25rem;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(380px, 0.9fr);
        gap: 1.25rem;
        align-items: start;
    }

    .settings-card-subtitle,
    .settings-type-heading small,
    .settings-help,
    .settings-note {
        color: var(--color-text-muted);
        line-height: 1.5;
    }

    .settings-card-subtitle {
        margin: 0.3rem 0 0;
        font-size: 0.88rem;
    }

    .settings-form-grid,
    .settings-type-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
    }

    .settings-form-field--full,
    .settings-type-description {
        grid-column: 1 / -1;
    }

    .settings-money-field {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
    }

    .settings-money-field > span {
        display: grid;
        place-items: center;
        border: 1px solid var(--color-border);
        border-right: 0;
        border-radius: var(--radius-small) 0 0 var(--radius-small);
        background: #f8fafc;
        font-weight: 700;
    }

    .settings-money-field .form-control {
        border-radius: 0 var(--radius-small) var(--radius-small) 0;
    }

    .settings-checkbox {
        display: flex;
        gap: 0.8rem;
        padding: 1rem;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
        background: #f8fafc;
        cursor: pointer;
    }

    .settings-checkbox input,
    .settings-inline-checkbox input {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .settings-checkbox span,
    .settings-checkbox small {
        display: block;
    }

    .settings-actions,
    .settings-type-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1rem;
    }

    .settings-status-list {
        display: grid;
        gap: 0;
        padding-top: 0;
        padding-bottom: 0;
    }

    .settings-status-list > div {
        display: grid;
        grid-template-columns: minmax(135px, 0.7fr) minmax(0, 1fr);
        gap: 1rem;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--color-border);
    }

    .settings-status-list > div:last-child {
        border-bottom: 0;
    }

    .settings-status-list span {
        color: var(--color-text-muted);
        overflow-wrap: anywhere;
    }

    .settings-status-list strong {
        overflow-wrap: anywhere;
    }

    .settings-status--ok { color: #15803d; }
    .settings-status--warning { color: #a16207; }
    .settings-status--danger { color: #b91c1c; }

    .settings-note {
        display: block;
        font-size: 0.82rem;
    }

    .settings-type-card {
        display: grid;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-medium);
        background: #fff;
    }

    .settings-type-card--new {
        border-style: dashed;
        background: #f8fafc;
    }

    .settings-type-heading,
    .settings-type-actions,
    .settings-inline-checkbox {
        display: flex;
        align-items: center;
    }

    .settings-type-heading {
        justify-content: space-between;
        gap: 1rem;
    }

    .settings-type-heading > div,
    .settings-type-heading small {
        display: block;
    }

    .settings-type-actions {
        justify-content: space-between;
    }

    .settings-inline-checkbox {
        gap: 0.55rem;
        font-weight: 600;
    }

    .settings-color {
        min-height: 46px;
        padding: 0.35rem;
    }

    @media (max-width: 1180px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }

        .settings-status-stack {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .settings-form-grid,
        .settings-type-grid,
        .settings-status-stack {
            grid-template-columns: 1fr;
        }

        .settings-form-field--full,
        .settings-type-description {
            grid-column: auto;
        }

        .settings-type-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .settings-type-actions .btn,
        .settings-actions .btn {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>
