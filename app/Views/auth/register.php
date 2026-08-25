<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */

$this->extend('layouts.guest', [
    'title' => 'Registreren',
]);

$oldInput = $helpers->old->all();

$value = static function (
    string $field,
    mixed $default = ''
) use ($oldInput): mixed {
    return array_key_exists($field, $oldInput)
        ? $oldInput[$field]
        : $default;
};

$selected = static function (
    mixed $current,
    string $expected
): string {
    return (string) $current === $expected
        ? ' selected'
        : '';
};

$gdprAccepted = filter_var(
    $value('gdpr_consent', false),
    FILTER_VALIDATE_BOOL
);
?>

<?php $this->startSection('styles'); ?>

<style>
    .guest-layout {
        align-items: flex-start;
        padding:
            clamp(1rem, 3vw, 3rem)
            clamp(0.75rem, 3vw, 3rem);
    }

    .guest-layout__content {
        width: 100%;
        max-width: none;
    }

    .registration {
        width: min(100%, 1280px);
        margin: 0 auto;
    }

    .registration-card {
        overflow: hidden;
        background: #ffffff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 16px;
        box-shadow:
            0 18px 50px rgba(15, 23, 42, 0.08),
            0 2px 8px rgba(15, 23, 42, 0.05);
    }

    .registration-card__brand {
        display: flex;
        min-height: clamp(90px, 9vw, 130px);
        align-items: center;
        justify-content: center;
        padding: 1.25rem 2rem;
        background: var(--color-primary, #b5121b);
    }

    .registration-card__logo {
        display: block;
        width: auto;
        max-width: clamp(150px, 17vw, 230px);
        max-height: 80px;
    }

    .registration-card__header {
        padding:
            clamp(1.5rem, 3vw, 2.5rem)
            clamp(1rem, 4vw, 3rem)
            1.25rem;
        text-align: center;
    }

    .registration-card__header h1 {
        margin: 0 0 0.5rem;
        color: var(--color-text, #0f172a);
        font-size: clamp(1.7rem, 3vw, 2.3rem);
        line-height: 1.15;
    }

    .registration-card__header p {
        max-width: 680px;
        margin: 0 auto;
        color: var(--color-text-muted, #64748b);
        font-size: clamp(0.9rem, 1.5vw, 1rem);
        line-height: 1.6;
    }

    .registration-form {
        display: grid;
        gap: clamp(1rem, 2vw, 1.5rem);
        padding:
            0
            clamp(1rem, 4vw, 3rem)
            clamp(1.5rem, 4vw, 3rem);
    }

    .registration-sections {
        display: grid;
        grid-template-columns:
            repeat(
                auto-fit,
                minmax(min(100%, 500px), 1fr)
            );
        align-items: start;
        gap: clamp(1rem, 2vw, 1.5rem);
    }

    .registration-section {
        min-width: 0;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
    }

    .registration-section__header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--color-border, #dfe4ec);
        background: #f8fafc;
    }

    .registration-section__header h2 {
        margin: 0;
        color: var(--color-text, #0f172a);
        font-size: 1rem;
        font-weight: 750;
    }

    .registration-section__body {
        padding: clamp(1rem, 2vw, 1.35rem);
    }

    .registration-grid {
        display: grid;
        grid-template-columns:
            repeat(
                auto-fit,
                minmax(min(100%, 220px), 1fr)
            );
        gap: 1rem;
    }

    .registration-field {
        display: grid;
        min-width: 0;
        align-content: start;
        gap: 0.45rem;
    }

    .registration-field--full {
        grid-column: 1 / -1;
    }

    .registration-field label,
    .registration-label {
        color: var(--color-text, #0f172a);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .registration-field input,
    .registration-field select {
        width: 100%;
        min-width: 0;
        min-height: 44px;
        padding: 0.7rem 0.8rem;
        box-sizing: border-box;
        color: var(--color-text, #0f172a);
        background: #ffffff;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        font: inherit;
        transition:
            border-color 0.15s ease,
            box-shadow 0.15s ease;
    }

    .registration-field input:hover,
    .registration-field select:hover {
        border-color: #aeb8c7;
    }

    .registration-field input:focus,
    .registration-field select:focus {
        border-color: var(--color-primary, #b5121b);
        outline: none;
        box-shadow: 0 0 0 3px rgba(181, 18, 27, 0.12);
    }

    .registration-help {
        color: var(--color-text-muted, #64748b);
        font-size: 0.76rem;
        line-height: 1.45;
    }

    .registration-consent {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        color: var(--color-text, #0f172a);
        font-weight: 500;
        line-height: 1.55;
        cursor: pointer;
    }

    .registration-consent input {
        width: 18px;
        height: 18px;
        min-height: 18px;
        margin: 0.2rem 0 0;
        flex: 0 0 auto;
        accent-color: var(--color-primary, #b5121b);
    }

    .registration-notice {
        padding: 1rem 1.15rem;
        color: #854d0e;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        font-size: 0.9rem;
        line-height: 1.55;
    }

    .registration-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 0.25rem;
    }

    .registration-actions a {
        color: var(--color-primary, #b5121b);
        font-size: 0.9rem;
        font-weight: 650;
        text-decoration: none;
    }

    .registration-actions a:hover {
        text-decoration: underline;
    }

    .registration-actions .button {
        min-height: 44px;
        padding: 0.75rem 1.25rem;
    }

    @media (min-width: 1180px) {
        .registration-sections {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .guest-layout {
            padding: 0;
        }

        .registration {
            width: 100%;
        }

        .registration-card {
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .registration-card__brand {
            min-height: 90px;
        }

        .registration-card__header {
            padding: 1.5rem 1rem 1rem;
        }

        .registration-form {
            padding: 0 0.85rem 1.5rem;
        }

        .registration-sections {
            grid-template-columns: 1fr;
        }

        .registration-grid {
            grid-template-columns: 1fr;
        }

        .registration-field--full {
            grid-column: auto;
        }

        .registration-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .registration-actions a {
            text-align: center;
        }

        .registration-actions .button {
            width: 100%;
        }
    }
</style>

<?php $this->endSection(); ?>

<?php $this->startSection('content'); ?>

<div class="registration">
    <section class="registration-card">
        <div class="registration-card__brand">
            <img
                class="registration-card__logo"
                src="<?= $this->escape(
                    $helpers->asset->url(
                        'images/jan-primus-logo.png'
                    )
                ) ?>"
                alt="vzw Jan Primus"
            >
        </div>

        <header class="registration-card__header">
            <h1>Lid worden</h1>

            <p>
                Maak je ledenprofiel en gebruikersaccount aan.
                Na registratie wordt je aanvraag door een administrator
                beoordeeld.
            </p>
        </header>

        <?= $helpers->form->open(
            $helpers->url->to('/register'),
            'POST',
            [
                'class' => 'registration-form',
                'autocomplete' => 'on',
                'novalidate' => true,
            ]
        ) ?>

        <div class="registration-sections">
            <section class="registration-section">
                <header class="registration-section__header">
                    <h2>Persoonsgegevens</h2>
                </header>

                <div class="registration-section__body registration-grid">
                    <div class="registration-field">
                        <label for="voornaam">
                            Voornaam *
                        </label>

                        <input
                            type="text"
                            id="voornaam"
                            name="voornaam"
                            value="<?= $this->escape(
                                (string) $value('voornaam')
                            ) ?>"
                            required
                            autocomplete="given-name"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'voornaam'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="achternaam">
                            Achternaam *
                        </label>

                        <input
                            type="text"
                            id="achternaam"
                            name="achternaam"
                            value="<?= $this->escape(
                                (string) $value('achternaam')
                            ) ?>"
                            required
                            autocomplete="family-name"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'achternaam'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="email">
                            E-mailadres *
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= $this->escape(
                                (string) $value('email')
                            ) ?>"
                            required
                            autocomplete="email"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'email'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="telefoon">
                            Telefoon
                        </label>

                        <input
                            type="tel"
                            id="telefoon"
                            name="telefoon"
                            value="<?= $this->escape(
                                (string) $value('telefoon')
                            ) ?>"
                            autocomplete="tel"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'telefoon'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="geboortedatum">
                            Geboortedatum
                        </label>

                        <input
                            type="text"
                            id="geboortedatum"
                            name="geboortedatum"
                            value="<?= $this->escape(
                                (string) $value('geboortedatum')
                            ) ?>"
                            placeholder="DD/mm/YYYY"
                            pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                            maxlength="10"
                            autocomplete="bday"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'geboortedatum'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="geslacht">
                            Geslacht
                        </label>

                        <select
                            id="geslacht"
                            name="geslacht"
                        >
                            <option value="">
                                — Selecteer —
                            </option>

                            <option
                                value="M"<?= $selected(
                                    $value('geslacht'),
                                    'M'
                                ) ?>
                            >
                                Man
                            </option>

                            <option
                                value="V"<?= $selected(
                                    $value('geslacht'),
                                    'V'
                                ) ?>
                            >
                                Vrouw
                            </option>

                            <option
                                value="X"<?= $selected(
                                    $value('geslacht'),
                                    'X'
                                ) ?>
                            >
                                X
                            </option>
                        </select>

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'geslacht'
                        ) ?>
                    </div>
                </div>
            </section>

            <section class="registration-section">
                <header class="registration-section__header">
                    <h2>Adres</h2>
                </header>

                <div class="registration-section__body registration-grid">
                    <div class="registration-field">
                        <label for="straat">
                            Straat
                        </label>

                        <input
                            type="text"
                            id="straat"
                            name="straat"
                            value="<?= $this->escape(
                                (string) $value('straat')
                            ) ?>"
                            autocomplete="address-line1"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'straat'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="huisnummer">
                            Huisnummer
                        </label>

                        <input
                            type="text"
                            id="huisnummer"
                            name="huisnummer"
                            value="<?= $this->escape(
                                (string) $value('huisnummer')
                            ) ?>"
                            autocomplete="address-line2"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'huisnummer'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="bus">
                            Bus
                        </label>

                        <input
                            type="text"
                            id="bus"
                            name="bus"
                            value="<?= $this->escape(
                                (string) $value('bus')
                            ) ?>"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'bus'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="postcode">
                            Postcode
                        </label>

                        <input
                            type="text"
                            id="postcode"
                            name="postcode"
                            value="<?= $this->escape(
                                (string) $value('postcode')
                            ) ?>"
                            autocomplete="postal-code"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'postcode'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="gemeente">
                            Gemeente
                        </label>

                        <input
                            type="text"
                            id="gemeente"
                            name="gemeente"
                            value="<?= $this->escape(
                                (string) $value('gemeente')
                            ) ?>"
                            autocomplete="address-level2"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'gemeente'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="land">
                            Land
                        </label>

                        <input
                            type="text"
                            id="land"
                            name="land"
                            value="<?= $this->escape(
                                (string) $value(
                                    'land',
                                    'België'
                                )
                            ) ?>"
                            autocomplete="country-name"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'land'
                        ) ?>
                    </div>
                </div>
            </section>

            <section class="registration-section">
                <header class="registration-section__header">
                    <h2>Administratieve gegevens</h2>
                </header>

                <div class="registration-section__body registration-grid">
                    <div class="registration-field">
                        <label for="rijksregisternummer">
                            Nationaal identificatienummer
                        </label>

                        <input
                            type="text"
                            id="rijksregisternummer"
                            name="rijksregisternummer"
                            value="<?= $this->escape(
                                (string) $value(
                                    'rijksregisternummer'
                                )
                            ) ?>"
                            maxlength="100"
                            autocomplete="off"
                        >

                        <small class="registration-help">
                            Voor Belgische leden is dit het rijksregisternummer.
                            Buitenlandse nummers mogen letters en leestekens bevatten.
                            Wordt versleuteld opgeslagen.
                        </small>

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'rijksregisternummer'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="tshirtmaat">
                            T-shirtmaat
                        </label>

                        <select
                            id="tshirtmaat"
                            name="tshirtmaat"
                        >
                            <option value="">
                                — Selecteer —
                            </option>

                            <?php foreach (
                                [
                                    'XS',
                                    'S',
                                    'M',
                                    'L',
                                    'XL',
                                    'XXL',
                                ] as $size
                            ): ?>
                                <option
                                    value="<?= $this->escape(
                                        $size
                                    ) ?>"<?= $selected(
                                        $value('tshirtmaat'),
                                        $size
                                    ) ?>
                                >
                                    <?= $this->escape($size) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'tshirtmaat'
                        ) ?>
                    </div>
                </div>
            </section>

            <section class="registration-section">
                <header class="registration-section__header">
                    <h2>Gebruikersaccount</h2>
                </header>

                <div class="registration-section__body registration-grid">
                    <div class="registration-field">
                        <label for="password">
                            Wachtwoord *
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >

                        <small class="registration-help">
                            Minstens 8 tekens.
                        </small>

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'password'
                        ) ?>
                    </div>

                    <div class="registration-field">
                        <label for="password_confirmation">
                            Wachtwoord herhalen *
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'password_confirmation'
                        ) ?>
                    </div>

                    <div class="registration-field registration-field--full">
                        <label class="registration-consent">
                            <input
                                type="checkbox"
                                name="gdpr_consent"
                                value="1"
                                required
                                <?= $gdprAccepted ? ' checked' : '' ?>
                            >

                            <span>
                                Ik ga akkoord met de verwerking van mijn
                                persoonsgegevens voor het leden- en
                                eventbeheer van AEFS. *
                            </span>
                        </label>

                        <?= $helpers->errorRenderer->field(
                            $helpers->errors,
                            'gdpr_consent'
                        ) ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="registration-notice">
            Na registratie blijft je account inactief tot een administrator
            je lidmaatschap heeft goedgekeurd.
        </div>

        <div class="registration-actions">
            <a href="<?= $this->escape(
                $helpers->url->to('/login')
            ) ?>">
                Ik heb al een account
            </a>

            <button
                type="submit"
                class="button button--primary"
            >
                Registratie verzenden
            </button>
        </div>

        <?= $helpers->form->close() ?>
    </section>
</div>

<?php $this->endSection(); ?>
