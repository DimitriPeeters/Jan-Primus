<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\Member;
use App\Support\BelgianDateTime;

/** @var ViewHelpers $helpers */
/** @var Member|null $lid */

$lid ??= null;

$isEdit = $lid instanceof Member;

$value = static function (
    string $field,
    mixed $default = ''
) use ($lid): mixed {
    if (!$lid instanceof Member) {
        return $default;
    }

    return match ($field) {
        'voornaam' => $lid->voornaam,
        'achternaam' => $lid->achternaam,
        'email' => $lid->email,
        'telefoon' => $lid->telefoon,
        'straat' => $lid->straat,
        'postcode' => $lid->postcode,
        'gemeente' => $lid->gemeente,
        'land' => $lid->land,
        'geboortedatum' => BelgianDateTime::formatDate(
            $lid->geboortedatum,
            ''
        ),
        'geslacht' => $lid->geslacht,
        'rijksregisternummer' => $lid->rijksregisternummer,
        'tshirtmaat' => $lid->tshirtmaat,
        'opmerkingen' => $lid->opmerkingen,
        default => $default,
    };
};

$selected = static function (
    mixed $current,
    string $expected
): string {
    return (string) $current === $expected
        ? ' selected'
        : '';
};

$checked = static function (bool $condition): string {
    return $condition ? ' checked' : '';
};

$cancelUrl = $isEdit
    ? $helpers->url->to('/members/' . $lid->lidId)
    : $helpers->url->to('/members');
?>

<style>
    .member-form {
        display: grid;
        gap: 1.25rem;
    }

    .member-form__section {
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--color-border, #dfe4ec);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .member-form__section-header {
        padding: 0.95rem 1rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--color-border, #dfe4ec);
    }

    .member-form__section-title {
        margin: 0;
        font-size: 1rem;
    }

    .member-form__section-body {
        padding: 1rem;
    }

    .member-form__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .member-form__field {
        min-width: 0;
    }

    .member-form__field--full {
        grid-column: 1 / -1;
    }

    .member-form__label {
        display: block;
        margin-bottom: 0.4rem;
        color: var(--color-text, #172033);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .member-form__required {
        color: var(--color-primary, #b5121b);
    }

    .member-form__control {
        width: 100%;
        min-height: 42px;
        padding: 0.65rem 0.8rem;
        box-sizing: border-box;
        color: var(--color-text, #172033);
        background: #fff;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        font: inherit;
    }

    .member-form__control:focus {
        border-color: var(--color-primary, #b5121b);
        outline: 3px solid rgba(181, 18, 27, 0.12);
    }

    textarea.member-form__control {
        min-height: 130px;
        resize: vertical;
    }

    .member-form__checks {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .member-form__check {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        cursor: pointer;
    }

    .member-form__check-input {
        width: 18px;
        height: 18px;
        accent-color: var(--color-primary, #b5121b);
    }

    .member-form__help {
        display: block;
        margin-top: 0.35rem;
        color: var(--color-text-muted, #64748b);
        font-size: 0.78rem;
        line-height: 1.4;
    }

    .member-form__actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 0.25rem;
    }

    @media (max-width: 760px) {
        .member-form__grid {
            grid-template-columns: 1fr;
        }

        .member-form__field--full {
            grid-column: auto;
        }

        .member-form__actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .member-form__actions .btn {
            width: 100%;
        }
    }
</style>

<div class="member-form">

    <section class="member-form__section">
        <header class="member-form__section-header">
            <h2 class="member-form__section-title">
                Persoonsgegevens
            </h2>
        </header>

        <div class="member-form__section-body">
            <div class="member-form__grid">
                <div class="member-form__field">
                    <label
                        for="voornaam"
                        class="member-form__label"
                    >
                        Voornaam
                        <span class="member-form__required">*</span>
                    </label>

                    <input
                        type="text"
                        id="voornaam"
                        name="voornaam"
                        value="<?= $this->escape(
                            (string) $value('voornaam')
                        ) ?>"
                        class="member-form__control"
                        required
                        autocomplete="given-name"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="achternaam"
                        class="member-form__label"
                    >
                        Achternaam
                        <span class="member-form__required">*</span>
                    </label>

                    <input
                        type="text"
                        id="achternaam"
                        name="achternaam"
                        value="<?= $this->escape(
                            (string) $value('achternaam')
                        ) ?>"
                        class="member-form__control"
                        required
                        autocomplete="family-name"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="email"
                        class="member-form__label"
                    >
                        E-mailadres
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= $this->escape(
                            (string) $value('email')
                        ) ?>"
                        class="member-form__control"
                        autocomplete="email"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="telefoon"
                        class="member-form__label"
                    >
                        Telefoon
                    </label>

                    <input
                        type="tel"
                        id="telefoon"
                        name="telefoon"
                        value="<?= $this->escape(
                            (string) $value('telefoon')
                        ) ?>"
                        class="member-form__control"
                        autocomplete="tel"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="geboortedatum"
                        class="member-form__label"
                    >
                        Geboortedatum
                    </label>

                    <input
                        type="text"
                        id="geboortedatum"
                        name="geboortedatum"
                        value="<?= $this->escape(
                            (string) $value('geboortedatum')
                        ) ?>"
                        class="member-form__control"
                        placeholder="DD/mm/YYYY"
                        pattern="(?:0[1-9]|[12][0-9]|3[01])/(?:0[1-9]|1[0-2])/[0-9]{4}"
                        maxlength="10"
                        autocomplete="bday"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="geslacht"
                        class="member-form__label"
                    >
                        Geslacht
                    </label>

                    <select
                        id="geslacht"
                        name="geslacht"
                        class="member-form__control"
                    >
                        <option value="">— Selecteer —</option>
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
                </div>
            </div>
        </div>
    </section>

    <section class="member-form__section">
        <header class="member-form__section-header">
            <h2 class="member-form__section-title">
                Adres
            </h2>
        </header>

        <div class="member-form__section-body">
            <div class="member-form__grid">
                <div class="member-form__field member-form__field--full">
                    <label
                        for="straat"
                        class="member-form__label"
                    >
                        Straat en huisnummer
                    </label>

                    <input
                        type="text"
                        id="straat"
                        name="straat"
                        value="<?= $this->escape(
                            (string) $value('straat')
                        ) ?>"
                        class="member-form__control"
                        autocomplete="street-address"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="postcode"
                        class="member-form__label"
                    >
                        Postcode
                    </label>

                    <input
                        type="text"
                        id="postcode"
                        name="postcode"
                        value="<?= $this->escape(
                            (string) $value('postcode')
                        ) ?>"
                        class="member-form__control"
                        autocomplete="postal-code"
                    >
                </div>

                <div class="member-form__field">
                    <label
                        for="gemeente"
                        class="member-form__label"
                    >
                        Gemeente
                    </label>

                    <input
                        type="text"
                        id="gemeente"
                        name="gemeente"
                        value="<?= $this->escape(
                            (string) $value('gemeente')
                        ) ?>"
                        class="member-form__control"
                        autocomplete="address-level2"
                    >
                </div>

                <div class="member-form__field member-form__field--full">
                    <label
                        for="land"
                        class="member-form__label"
                    >
                        Land
                    </label>

                    <input
                        type="text"
                        id="land"
                        name="land"
                        value="<?= $this->escape(
                            (string) $value('land', 'België')
                        ) ?>"
                        class="member-form__control"
                        autocomplete="country-name"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="member-form__section">
        <header class="member-form__section-header">
            <h2 class="member-form__section-title">
                Administratieve gegevens
            </h2>
        </header>

        <div class="member-form__section-body">
            <div class="member-form__grid">
                <div class="member-form__field">
                    <label
                        for="rijksregisternummer"
                        class="member-form__label"
                    >
                        Nationaal identificatienummer
                    </label>

                    <input
                        type="text"
                        id="rijksregisternummer"
                        name="rijksregisternummer"
                        value="<?= $this->escape(
                            (string) $value('rijksregisternummer')
                        ) ?>"
                        class="member-form__control"
                        maxlength="100"
                        autocomplete="off"
                    >

                    <small class="member-form__help">
                        <?php if (
                            $lid instanceof Member
                            && $lid->nationaalIdentificatienummerOnleesbaar
                        ): ?>
                            De bestaande legacywaarde kan niet worden ontsleuteld.
                            Laat dit veld leeg om die waarde te bewaren, of voer het
                            correcte nummer opnieuw in om ze veilig te vervangen.
                        <?php else: ?>
                            Voor Belgische leden is dit het rijksregisternummer.
                            Buitenlandse nummers mogen letters en leestekens bevatten.
                            Het nummer wordt versleuteld opgeslagen.
                        <?php endif; ?>
                    </small>
                </div>

                <div class="member-form__field">
                    <label
                        for="tshirtmaat"
                        class="member-form__label"
                    >
                        T-shirtmaat
                    </label>

                    <select
                        id="tshirtmaat"
                        name="tshirtmaat"
                        class="member-form__control"
                    >
                        <option value="">— Selecteer —</option>

                        <?php foreach (
                            ['XS', 'S', 'M', 'L', 'XL', 'XXL']
                            as $size
                        ): ?>
                            <option
                                value="<?= $this->escape($size) ?>"
                                <?= $selected(
                                    $value('tshirtmaat'),
                                    $size
                                ) ?>
                            >
                                <?= $this->escape($size) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="member-form__field">
                    <label class="member-form__label">
                        Lidstatus
                    </label>

                    <div class="member-form__checks">
                        <label class="member-form__check">
                            <input
                                type="checkbox"
                                name="actief"
                                value="1"
                                class="member-form__check-input"
                                <?= $checked(
                                    $isEdit
                                        ? $lid->actief
                                        : true
                                ) ?>
                            >

                            <span>Actief lid</span>
                        </label>
                    </div>
                </div>

                <div class="member-form__field member-form__field--full">
                    <label
                        for="opmerkingen"
                        class="member-form__label"
                    >
                        Opmerkingen
                    </label>

                    <textarea
                        id="opmerkingen"
                        name="opmerkingen"
                        rows="5"
                        class="member-form__control"
                    ><?= $this->escape(
                        (string) $value('opmerkingen')
                    ) ?></textarea>
                </div>

                <div class="member-form__field member-form__field--full">
                    <label class="member-form__check">
                        <input
                            type="checkbox"
                            name="gdpr_consent"
                            value="1"
                            class="member-form__check-input"
                            <?= $checked(
                                $isEdit
                                    ? $lid->gdprConsent
                                    : false
                            ) ?>
                        >

                        <span>
                            GDPR-toestemming ontvangen
                        </span>
                    </label>

                    <small class="member-form__help">
                        Activeer dit uitsluitend wanneer het lid aantoonbaar
                        toestemming heeft gegeven.
                    </small>
                </div>
            </div>
        </div>
    </section>

    <div class="member-form__actions">
        <a
            href="<?= $this->escape($cancelUrl) ?>"
            class="btn btn-secondary"
        >
            Annuleren
        </a>

        <button
            type="submit"
            class="btn btn-success"
        >
            <?= $isEdit
                ? 'Wijzigingen opslaan'
                : 'Lid aanmaken' ?>
        </button>
    </div>

</div>
