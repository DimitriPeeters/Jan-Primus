<?php

use AEFS\Core\Auth;
use AEFS\Core\View\Helper\ViewHelpers;
use App\Models\User;

/** @var ViewHelpers $helpers */
/** @var string|null $title */

$user = Auth::user();

$firstName = is_array($user)
    ? (string) ($user['voornaam'] ?? '')
    : '';

$lastName = is_array($user)
    ? (string) ($user['achternaam'] ?? '')
    : '';

$role = is_array($user)
    ? (string) ($user['rol'] ?? '')
    : '';

$roleLabel = match ($role) {
    User::ROLE_ADMIN => 'Administrator',
    User::ROLE_MEMBER => 'Lid',
    default => ucfirst($role),
};

$initial = '?';

if ($firstName !== '') {
    $firstCharacter = function_exists('mb_substr')
        ? mb_substr($firstName, 0, 1)
        : substr($firstName, 0, 1);

    $initial = function_exists('mb_strtoupper')
        ? mb_strtoupper($firstCharacter)
        : strtoupper($firstCharacter);
}
?>

<header class="app-header">
    <div class="app-header__content">
        <h1 class="app-header__title">
            <?= $this->escape(
                $title ?? 'AEFS Eventbeheer'
            ) ?>
        </h1>

        <div class="app-header__actions">
            <div class="app-header__user">
                <div class="app-header__avatar">
                    <?= $this->escape($initial) ?>
                </div>

                <div class="app-header__identity">
                    <strong class="app-header__name">
                        <?= $this->escape(
                            trim($firstName . ' ' . $lastName)
                        ) ?>
                    </strong>

                    <?php if ($roleLabel !== ''): ?>
                        <span class="app-header__role">
                            <?= $this->escape($roleLabel) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?= $helpers->form->open(
                $helpers->url->to('/logout'),
                'POST',
                [
                    'class' => 'app-header__logout-form',
                ]
            ) ?>
                <button
                    type="submit"
                    class="button button--secondary app-header__logout-button"
                >
                    Afmelden
                </button>
            <?= $helpers->form->close() ?>
        </div>
    </div>
</header>