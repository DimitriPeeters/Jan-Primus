<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Member $lid */
/** @var string|null $title */

$display = static function (mixed $value): string {
    if ($value === null) {
        return '—';
    }

    $value = trim((string) $value);

    return $value === ''
        ? '—'
        : $value;
};

$birthDate = BelgianDateTime::formatDate(
    $lid->geboortedatum
);
$genderLabel = [
    'M' => 'Man',
    'V' => 'Vrouw',
    'X' => 'X',
][$lid->geslacht ?? ''] ?? $display($lid->geslacht);

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Mijn profiel',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="profile-page">
    <header class="profile-page__header">
        <div>
            <h1 class="profile-page__title">
                Mijn profiel
            </h1>

            <p class="profile-page__subtitle">
                <?= $this->escape($lid->fullName()) ?>
            </p>
        </div>

        <a
            href="<?= $this->escape(
                $helpers->url->to('/profile/edit')
            ) ?>"
            class="btn btn-primary"
        >
            Profiel wijzigen
        </a>
    </header>

    <div class="profile-page__grid">
        <section class="profile-card">
            <header class="profile-card__header">
                <h2 class="profile-card__title">
                    Persoonsgegevens
                </h2>
            </header>

            <table class="profile-data">
                <tbody>
                <tr>
                    <th scope="row">Voornaam</th>
                    <td><?= $this->escape($display($lid->voornaam)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Achternaam</th>
                    <td><?= $this->escape($display($lid->achternaam)) ?></td>
                </tr>
                <tr>
                    <th scope="row">E-mailadres</th>
                    <td><?= $this->escape($display($lid->email)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Telefoon</th>
                    <td><?= $this->escape($display($lid->telefoon)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Geboortedatum</th>
                    <td><?= $this->escape($birthDate) ?></td>
                </tr>
                <tr>
                    <th scope="row">Geslacht</th>
                    <td><?= $this->escape($genderLabel) ?></td>
                </tr>
                </tbody>
            </table>
        </section>

        <section class="profile-card">
            <header class="profile-card__header">
                <h2 class="profile-card__title">
                    Adres
                </h2>
            </header>

            <table class="profile-data">
                <tbody>
                <tr>
                    <th scope="row">Straat</th>
                    <td><?= $this->escape($display($lid->straat)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Postcode</th>
                    <td><?= $this->escape($display($lid->postcode)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Gemeente</th>
                    <td><?= $this->escape($display($lid->gemeente)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Land</th>
                    <td><?= $this->escape($display($lid->land)) ?></td>
                </tr>
                </tbody>
            </table>
        </section>

        <section class="profile-card profile-card--full">
            <header class="profile-card__header">
                <h2 class="profile-card__title">
                    Administratieve gegevens
                </h2>
            </header>

            <table class="profile-data">
                <tbody>
                <tr>
                    <th scope="row">Nationaal identificatienummer</th>
                    <td>
                        <?php if ($lid->nationaalIdentificatienummerOnleesbaar): ?>
                            De bestaande legacywaarde kan niet worden ontsleuteld.
                            Voer het nummer opnieuw in via Profiel wijzigen.
                        <?php else: ?>
                            <?= $this->escape(
                                $display($lid->rijksregisternummer)
                            ) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">T-shirtmaat</th>
                    <td><?= $this->escape($display($lid->tshirtmaat)) ?></td>
                </tr>
                <tr>
                    <th scope="row">Lidstatus</th>
                    <td><?= $lid->actief ? 'Actief' : 'Inactief' ?></td>
                </tr>
                <tr>
                    <th scope="row">Toegetreden op</th>
                    <td><?= $this->escape(
                        BelgianDateTime::formatDate($lid->toegetredenOp)
                    ) ?></td>
                </tr>
                <tr>
                    <th scope="row">Uitgetreden op</th>
                    <td><?= $this->escape(
                        BelgianDateTime::formatDate($lid->uitgetredenOp)
                    ) ?></td>
                </tr>
                <tr>
                    <th scope="row">GDPR-toestemming</th>
                    <td><?= $lid->gdprConsent ? 'Ja' : 'Nee' ?></td>
                </tr>
                </tbody>
            </table>
        </section>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .profile-page {
        display: grid;
        gap: 1.25rem;
    }

    .profile-page__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .profile-page__title {
        margin: 0 0 0.35rem;
        font-size: 1.75rem;
    }

    .profile-page__subtitle {
        margin: 0;
        color: var(--color-text-muted, #64748b);
    }

    .profile-page__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
    }

    .profile-card {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .profile-card--full {
        grid-column: 1 / -1;
    }

    .profile-card__header {
        padding: 0.95rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--color-border, #dfe4ec);
    }

    .profile-card__title {
        margin: 0;
        font-size: 1rem;
    }

    .profile-data {
        width: 100%;
        border-collapse: collapse;
    }

    .profile-data th,
    .profile-data td {
        padding: 0.8rem 1rem;
        border-top: 1px solid var(--color-border, #e2e7ef);
        text-align: left;
        vertical-align: top;
    }

    .profile-data tr:first-child th,
    .profile-data tr:first-child td {
        border-top: 0;
    }

    .profile-data th {
        width: 190px;
        color: #5c6b82;
        background: #fafbfc;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    @media (max-width: 800px) {
        .profile-page__grid {
            grid-template-columns: 1fr;
        }

        .profile-card--full {
            grid-column: auto;
        }
    }

    @media (max-width: 620px) {
        .profile-page__header {
            align-items: stretch;
            flex-direction: column;
        }

        .profile-page__header .btn {
            width: 100%;
        }

        .profile-data th,
        .profile-data td {
            display: block;
            width: auto;
        }
    }
</style>
<?php $this->endSection(); ?>
