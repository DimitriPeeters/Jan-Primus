<?php

use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\User;

/** @var ViewHelpers $helpers */
/** @var User $gebruiker */

$oldInput = $helpers->old->all();

$value = static function (
    string $field,
    mixed $default = ''
) use ($oldInput): mixed {
    return array_key_exists($field, $oldInput)
        ? $oldInput[$field]
        : $default;
};

$selectedRole = (string) $value(
    'rol',
    $gebruiker->rol
);

$active = array_key_exists('actief', $oldInput)
    ? filter_var($oldInput['actief'], FILTER_VALIDATE_BOOL)
    : $gebruiker->actief;

$mailBlacklist = array_key_exists('mail_blacklist', $oldInput)
    ? filter_var($oldInput['mail_blacklist'], FILTER_VALIDATE_BOOL)
    : $gebruiker->mailBlacklist;
?>

<div class="user-form">
    <div class="user-form__identity">
        <div>
            <span class="user-form__identity-label">Lid</span>
            <strong><?= $this->escape($gebruiker->fullName()) ?></strong>
        </div>

        <div>
            <span class="user-form__identity-label">E-mailadres</span>
            <strong><?= $this->escape($gebruiker->email) ?></strong>
        </div>
    </div>

    <div class="user-form__grid">
        <label class="user-form__field">
            <span>Rol *</span>
            <select name="rol" required>
                <option
                    value="<?= User::ROLE_MEMBER ?>"
                    <?= $selectedRole === User::ROLE_MEMBER
                        ? ' selected'
                        : '' ?>
                >
                    Lid
                </option>
                <option
                    value="<?= User::ROLE_ADMIN ?>"
                    <?= $selectedRole === User::ROLE_ADMIN
                        ? ' selected'
                        : '' ?>
                >
                    Administrator
                </option>
            </select>
        </label>
    </div>

    <div class="user-form__checks">
        <label>
            <input type="hidden" name="actief" value="0">
            <input
                type="checkbox"
                name="actief"
                value="1"
                <?= $active ? ' checked' : '' ?>
            >
            Account goedgekeurd en actief
        </label>

        <label>
            <input type="hidden" name="mail_blacklist" value="0">
            <input
                type="checkbox"
                name="mail_blacklist"
                value="1"
                <?= $mailBlacklist ? ' checked' : '' ?>
            >
            Mail blacklist
        </label>
    </div>

    <p class="user-form__help">
        Bij activatie of deactivatie wordt de status van het gekoppelde
        ledenprofiel automatisch gelijkgezet.
    </p>

    <div class="user-form__actions">
        <a
            href="<?= $this->escape(
                $helpers->url->to('/users')
            ) ?>"
            class="btn btn-secondary"
        >
            Annuleren
        </a>

        <button
            type="submit"
            class="btn btn-success"
        >
            Instellingen opslaan
        </button>
    </div>
</div>

<style>
    .user-form {
        display: grid;
        gap: 1.25rem;
        padding: 1.25rem;
    }

    .user-form__identity {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 1rem;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        background: #f8fafc;
    }

    .user-form__identity > div {
        display: grid;
        gap: 0.25rem;
    }

    .user-form__identity-label {
        color: var(--color-text-muted, #64748b);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .user-form__grid {
        display: grid;
        grid-template-columns: minmax(0, 360px);
        gap: 1rem;
    }

    .user-form__field {
        display: grid;
        gap: 0.4rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .user-form__field select {
        width: 100%;
        min-height: 42px;
        padding: 0.65rem 0.8rem;
        box-sizing: border-box;
        border: 1px solid var(--color-border, #d8dee9);
        border-radius: 8px;
        background: #fff;
        font: inherit;
        font-weight: 400;
    }

    .user-form__checks {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem 1.5rem;
    }

    .user-form__checks label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .user-form__help {
        margin: 0;
        color: var(--color-text-muted, #64748b);
        font-size: 0.85rem;
    }

    .user-form__actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }

    @media (max-width: 720px) {
        .user-form__identity {
            grid-template-columns: 1fr;
        }

        .user-form__actions {
            flex-direction: column-reverse;
        }
    }
</style>