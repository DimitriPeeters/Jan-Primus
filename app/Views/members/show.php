<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Member $lid */
/** @var MemberGroup[] $groepen */
/** @var array<int, array<string, mixed>> $logs */
/** @var string|null $title */

$logs ??= [];
$groepen ??= [];

$memberUrl = $helpers->url->to(
    '/members/' . $lid->lidId
);

$editUrl = $helpers->url->to(
    '/members/' . $lid->lidId . '/edit'
);

$listUrl = $helpers->url->to('/members');

$displayValue = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $value = trim((string) $value);

    return $value === '' ? '—' : $value;
};

$birthDate = BelgianDateTime::formatDate(
    $lid->geboortedatum
);

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? $lid->fullName(),
    ]
);
?>

<?php $this->startSection('styles'); ?>
<style>
    .member-detail {
        display: grid;
        gap: 1.25rem;
    }

    .member-detail__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .member-detail__title {
        margin: 0 0 0.35rem;
        font-size: 1.75rem;
        line-height: 1.2;
    }

    .member-detail__subtitle {
        margin: 0;
        color: var(--color-text-muted, #64748b);
    }

    .member-detail__actions {
        display: flex;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .member-detail__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
    }

    .member-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .member-card--full {
        grid-column: 1 / -1;
    }

    .member-card__header {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid var(--color-border, #dfe4ec);
        background: #f8fafc;
    }

    .member-card__title {
        margin: 0;
        font-size: 1rem;
    }

    .member-card__body {
        padding: 0;
    }

    .member-data {
        width: 100%;
        border-collapse: collapse;
    }

    .member-data th,
    .member-data td {
        padding: 0.8rem 1rem;
        border-top: 1px solid var(--color-border, #e2e7ef);
        vertical-align: top;
        text-align: left;
    }

    .member-data tr:first-child th,
    .member-data tr:first-child td {
        border-top: 0;
    }

    .member-data th {
        width: 180px;
        color: #5c6b82;
        background: #fafbfc;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .member-data a {
        color: var(--color-primary, #b5121b);
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

    .member-status--neutral {
        color: #475569;
        background: #e2e8f0;
    }

    .member-groups {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .member-group {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        color: #7f1d1d;
        background: #fee2e2;
        font-size: 0.78rem;
        font-weight: 700;
        text-decoration: none;
    }

    .member-group:hover {
        color: #7f1d1d;
        background: #fecaca;
        text-decoration: none;
    }

    .member-sensitive-warning {
        color: #92400e;
        font-weight: 600;
    }

    .member-notes {
        min-height: 120px;
        padding: 1rem;
        line-height: 1.6;
        white-space: pre-line;
    }

    .member-detail__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 900px) {
        .member-detail__grid {
            grid-template-columns: 1fr;
        }

        .member-card--full {
            grid-column: auto;
        }
    }

    @media (max-width: 680px) {
        .member-detail__header,
        .member-detail__footer {
            align-items: stretch;
            flex-direction: column;
        }

        .member-detail__actions {
            width: 100%;
        }

        .member-detail__actions .btn,
        .member-detail__footer .btn {
            width: 100%;
        }

        .member-data th,
        .member-data td {
            display: block;
            width: auto;
        }

        .member-data th {
            padding-bottom: 0.25rem;
        }

        .member-data td {
            padding-top: 0.25rem;
        }
    }
</style>
<?php $this->endSection(); ?>

<?php $this->startSection('content'); ?>
<div class="member-detail">

    <header class="member-detail__header">
        <div>
            <h1 class="member-detail__title">
                <?= $this->escape($lid->fullName()) ?>
            </h1>

            <p class="member-detail__subtitle">
                Ledenfiche
            </p>
        </div>

        <div class="member-detail__actions">
            <a
                href="<?= $this->escape($editUrl) ?>"
                class="btn btn-primary"
            >
                Wijzigen
            </a>
        </div>
    </header>

    <div class="member-detail__grid">

        <section class="member-card">
            <header class="member-card__header">
                <h2 class="member-card__title">
                    Persoonsgegevens
                </h2>
            </header>

            <div class="member-card__body">
                <table class="member-data">
                    <tbody>
                    <tr>
                        <th scope="row">Voornaam</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->voornaam)
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Achternaam</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->achternaam)
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">E-mailadres</th>
                        <td>
                            <?php if ($lid->hasEmail()): ?>
                                <a
                                    href="mailto:<?= $this->escape(
                                        (string) $lid->email
                                    ) ?>"
                                >
                                    <?= $this->escape(
                                        (string) $lid->email
                                    ) ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Telefoon</th>
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
                                        (string) $lid->telefoon
                                    ) ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Geboortedatum</th>
                        <td><?= $this->escape($birthDate) ?></td>
                    </tr>

                    <tr>
                        <th scope="row">Leeftijd</th>
                        <td>
                            <?= $lid->age() !== null
                                ? $this->escape((string) $lid->age())
                                : '—' ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Geslacht</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->geslacht)
                            ) ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="member-card">
            <header class="member-card__header">
                <h2 class="member-card__title">
                    Adres
                </h2>
            </header>

            <div class="member-card__body">
                <table class="member-data">
                    <tbody>
                    <tr>
                        <th scope="row">Straat</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->straat)
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Postcode</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->postcode)
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Gemeente</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->gemeente)
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Land</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->land)
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Volledig adres</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->fullAddress())
                            ) ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="member-card">
            <header class="member-card__header">
                <h2 class="member-card__title">
                    Lidmaatschap
                </h2>
            </header>

            <div class="member-card__body">
                <table class="member-data">
                    <tbody>
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <?php if ($lid->isActive()): ?>
                                <span class="member-status member-status--active">
                                    Actief
                                </span>
                            <?php else: ?>
                                <span class="member-status member-status--inactive">
                                    Inactief
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">GDPR</th>
                        <td>
                            <?php if ($lid->gdprAccepted()): ?>
                                <span class="member-status member-status--active">
                                    Toegestaan
                                </span>
                            <?php else: ?>
                                <span class="member-status member-status--neutral">
                                    Niet toegestaan
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Groepen</th>
                        <td>
                            <?php if ($groepen === []): ?>
                                —
                            <?php else: ?>
                                <div class="member-groups">
                                    <?php foreach ($groepen as $groep): ?>
                                        <a
                                            class="member-group"
                                            href="<?= $this->escape(
                                                $helpers->url->to(
                                                    '/members/groups?groep='
                                                    . $groep->groepId
                                                )
                                            ) ?>"
                                        >
                                            <?= $this->escape($groep->naam) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">GDPR-datum</th>
                        <td>
                            <?= $this->escape(
                                BelgianDateTime::formatDateTime(
                                    $lid->gdprTimestamp
                                )
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">T-shirtmaat</th>
                        <td>
                            <?= $this->escape(
                                $displayValue($lid->tshirtmaat)
                            ) ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="member-card">
            <header class="member-card__header">
                <h2 class="member-card__title">
                    Administratieve gegevens
                </h2>
            </header>

            <div class="member-card__body">
                <table class="member-data">
                    <tbody>
                    <tr>
                        <th scope="row">Nationaal identificatienummer</th>
                        <td>
                            <?php if ($lid->nationaalIdentificatienummerOnleesbaar): ?>
                                <span class="member-sensitive-warning">
                                    De bestaande legacywaarde kan niet worden
                                    ontsleuteld. Voer het nummer opnieuw in via
                                    Wijzigen.
                                </span>
                            <?php else: ?>
                                <?= $this->escape(
                                    $displayValue($lid->rijksregisternummer)
                                ) ?>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Aangemaakt</th>
                        <td>
                            <?= $this->escape(
                                BelgianDateTime::formatDateTime(
                                    $lid->aangemaaktOp
                                )
                            ) ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Laatst gewijzigd</th>
                        <td>
                            <?= $this->escape(
                                BelgianDateTime::formatDateTime(
                                    $lid->bijgewerktOp
                                )
                            ) ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="member-card member-card--full">
            <header class="member-card__header">
                <h2 class="member-card__title">
                    Opmerkingen
                </h2>
            </header>

            <div class="member-notes">
                <?= $this->escape(
                    $displayValue($lid->opmerkingen)
                ) ?>
            </div>
        </section>

        <section class="member-card member-card--full">
            <?= $this->component(
                'audit-log',
                [
                    'logs' => $logs,
                ]
            ) ?>
        </section>

    </div>

    <footer class="member-detail__footer">
        <a
            href="<?= $this->escape($listUrl) ?>"
            class="btn btn-secondary"
        >
            Terug naar leden
        </a>

        <a
            href="<?= $this->escape($editUrl) ?>"
            class="btn btn-primary"
        >
            Wijzigen
        </a>
    </footer>

</div>
<?php $this->endSection(); ?>
