<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Member $lid */
/** @var string|null $title */

$oldInput = $helpers->old->all();

$value = static function (
    string $field,
    mixed $current = ''
) use ($oldInput): mixed {
    return array_key_exists($field, $oldInput)
        ? $oldInput[$field]
        : $current;
};

$selected = static function (
    mixed $current,
    string $expected
): string {
    return (string) $current === $expected
        ? ' selected'
        : '';
};

$this->extend(
    'layouts.app',
    [
        'title' => $title ?? 'Mijn profiel wijzigen',
    ]
);
?>

<?php $this->startSection('content'); ?>
<div class="profile-edit">
    <header class="profile-edit__header">
        <div>
            <h1 class="profile-edit__title">
                Mijn profiel wijzigen
            </h1>

            <p class="profile-edit__subtitle">
                <?= $this->escape($lid->fullName()) ?>
            </p>
        </div>

        <a
            href="<?= $this->escape(
                $helpers->url->to('/profile')
            ) ?>"
            class="btn btn-secondary"
        >
            Annuleren
        </a>
    </header>

    <form
        method="post"
        action="<?= $this->escape(
            $helpers->url->to('/profile/update')
        ) ?>"
        class="profile-form"
        novalidate
    >
        <section class="profile-form__section">
            <header class="profile-form__section-header">
                <h2>Persoonsgegevens</h2>
            </header>

            <div class="profile-form__body profile-form__grid">
                <label class="profile-form__field">
                    <span>Voornaam *</span>
                    <input
                        type="text"
                        name="voornaam"
                        value="<?= $this->escape((string) $value(
                            'voornaam',
                            $lid->voornaam
                        )) ?>"
                        required
                        autocomplete="given-name"
                    >
                </label>

                <label class="profile-form__field">
                    <span>Achternaam *</span>
                    <input
                        type="text"
                        name="achternaam"
                        value="<?= $this->escape((string) $value(
                            'achternaam',
                            $lid->achternaam
                        )) ?>"
                        required
                        autocomplete="family-name"
                    >
                </label>

                <label class="profile-form__field">
                    <span>E-mailadres</span>
                    <input
                        type="email"
                        name="email"
                        value="<?= $this->escape((string) $value(
                            'email',
                            $lid->email
                        )) ?>"
                        autocomplete="email"
                    >
                </label>

                <label class="profile-form__field">
                    <span>Telefoon</span>
                    <input
                        type="tel"
                        name="telefoon"
                        value="<?= $this->escape((string) $value(
                            'telefoon',
                            $lid->telefoon
                        )) ?>"
                        autocomplete="tel"
                    >
                </label>

                <label class="profile-form__field">
                    <span>Geboortedatum</span>
                    <input
                        type="text"
                        name="geboortedatum"
                        value="<?= $this->escape((string) $value(
                            'geboortedatum',
                            BelgianDateTime::formatDate(
                                $lid->geboortedatum,
                                ''
                            )
                        )) ?>"
                        placeholder="DD/mm/YYYY"
                        pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                        maxlength="10"
                        autocomplete="bday"
                    >
                </label>

                <label class="profile-form__field">
                    <span>Geslacht</span>
                    <?php $gender = $value('geslacht', $lid->geslacht); ?>
                    <select name="geslacht">
                        <option value="">— Selecteer —</option>
                        <option value="M"<?= $selected($gender, 'M') ?>>Man</option>
                        <option value="V"<?= $selected($gender, 'V') ?>>Vrouw</option>
                        <option value="X"<?= $selected($gender, 'X') ?>>X</option>
                    </select>
                </label>
            </div>
        </section>

        <section class="profile-form__section">
            <header class="profile-form__section-header">
                <h2>Adres</h2>
            </header>

            <div class="profile-form__body profile-form__grid">
                <label class="profile-form__field profile-form__field--full">
                    <span>Straat en huisnummer</span>
                    <input
                        type="text"
                        name="straat"
                        value="<?= $this->escape((string) $value(
                            'straat',
                            $lid->straat
                        )) ?>"
                        autocomplete="street-address"
                    >
                </label>

                <label class="profile-form__field">
                    <span>Postcode</span>
                    <input
                        type="text"
                        name="postcode"
                        value="<?= $this->escape((string) $value(
                            'postcode',
                            $lid->postcode
                        )) ?>"
                        autocomplete="postal-code"
                    >
                </label>

                <label class="profile-form__field">
                    <span>Gemeente</span>
                    <input
                        type="text"
                        name="gemeente"
                        value="<?= $this->escape((string) $value(
                            'gemeente',
                            $lid->gemeente
                        )) ?>"
                        autocomplete="address-level2"
                    >
                </label>

                <label class="profile-form__field profile-form__field--full">
                    <span>Land</span>
                    <input
                        type="text"
                        name="land"
                        value="<?= $this->escape((string) $value(
                            'land',
                            $lid->land ?? 'België'
                        )) ?>"
                        autocomplete="country-name"
                    >
                </label>
            </div>
        </section>

        <section class="profile-form__section">
            <header class="profile-form__section-header">
                <h2>Administratieve gegevens</h2>
            </header>

            <div class="profile-form__body profile-form__grid">
                <label class="profile-form__field">
                    <span>IBAN</span>
                    <input
                        type="text"
                        name="rekeningnummer"
                        value="<?= $this->escape((string) $value(
                            'rekeningnummer',
                            $lid->rekeningnummer
                        )) ?>"
                        autocomplete="off"
                    >
                    <small>Wordt versleuteld opgeslagen.</small>
                </label>

                <label class="profile-form__field">
                    <span>Nationaal identificatienummer</span>
                    <input
                        type="text"
                        name="rijksregisternummer"
                        value="<?= $this->escape((string) $value(
                            'rijksregisternummer',
                            $lid->rijksregisternummer
                        )) ?>"
                        maxlength="100"
                        autocomplete="off"
                    >
                    <small>
                        <?php if ($lid->nationaalIdentificatienummerOnleesbaar): ?>
                            De bestaande legacywaarde kan niet worden ontsleuteld.
                            Laat dit veld leeg om die waarde te bewaren, of voer het
                            correcte nummer opnieuw in om ze veilig te vervangen.
                        <?php else: ?>
                            Voor Belgische leden is dit het rijksregisternummer.
                            Buitenlandse nummers mogen letters en leestekens bevatten.
                            Wordt versleuteld opgeslagen.
                        <?php endif; ?>
                    </small>
                </label>

                <label class="profile-form__field">
                    <span>T-shirtmaat</span>
                    <?php $shirtSize = $value('tshirtmaat', $lid->tshirtmaat); ?>
                    <select name="tshirtmaat">
                        <option value="">— Selecteer —</option>
                        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                            <option
                                value="<?= $this->escape($size) ?>"
                                <?= $selected($shirtSize, $size) ?>
                            >
                                <?= $this->escape($size) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <div class="profile-form__actions">
            <a
                href="<?= $this->escape(
                    $helpers->url->to('/profile')
                ) ?>"
                class="btn btn-secondary"
            >
                Annuleren
            </a>

            <button
                type="submit"
                class="btn btn-success"
            >
                Wijzigingen opslaan
            </button>
        </div>
    </form>
</div>
<?php $this->endSection(); ?>

<?php $this->startSection('styles'); ?>
<style>
    .profile-edit,
    .profile-form {
        display: grid;
        gap: 1.25rem;
    }

    .profile-edit__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .profile-edit__title {
        margin: 0 0 0.35rem;
        font-size: 1.75rem;
    }

    .profile-edit__subtitle {
        margin: 0;
        color: var(--color-text-muted, #64748b);
    }

    .profile-form__section {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .profile-form__section-header {
        padding: 0.95rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--color-border, #dfe4ec);
    }

    .profile-form__section-header h2 {
        margin: 0;
        font-size: 1rem;
    }

    .profile-form__body {
        padding: 1rem;
    }

    .profile-form__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .profile-form__field {
        display: grid;
        gap: 0.4rem;
        min-width: 0;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .profile-form__field--full {
        grid-column: 1 / -1;
    }

    .profile-form__field input,
    .profile-form__field select {
        width: 100%;
        min-height: 42px;
        padding: 0.65rem 0.8rem;
        box-sizing: border-box;
        color: var(--color-text, #172033);
        background: #fff;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        font: inherit;
        font-weight: 400;
    }

    .profile-form__field small {
        color: var(--color-text-muted, #64748b);
        font-weight: 400;
    }

    .profile-form__actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 720px) {
        .profile-form__grid {
            grid-template-columns: 1fr;
        }

        .profile-form__field--full {
            grid-column: auto;
        }
    }

    @media (max-width: 620px) {
        .profile-edit__header,
        .profile-form__actions {
            align-items: stretch;
            flex-direction: column;
        }

        .profile-edit__header .btn,
        .profile-form__actions .btn {
            width: 100%;
        }
    }
</style>
<?php $this->endSection(); ?>
