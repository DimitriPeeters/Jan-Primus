<?php

/** @var string|null $action */
/** @var string|null $value */
/** @var string|null $placeholder */
/** @var string|null $label */
/** @var string|null $submitText */
/** @var string|null $clearUrl */

$action = isset($action)
    ? trim((string) $action)
    : '';

$value = isset($value)
    ? (string) $value
    : '';

$placeholder = isset($placeholder)
    ? (string) $placeholder
    : 'Zoeken...';

$label = isset($label)
    ? trim((string) $label)
    : 'Zoeken';

$submitText = isset($submitText)
    ? trim((string) $submitText)
    : 'Zoeken';

$clearUrl = isset($clearUrl)
    ? trim((string) $clearUrl)
    : $action;
?>

<form
    method="get"
    action="<?= $this->escape($action) ?>"
    class="search-box"
>
    <div class="search-box__field">
        <label
            for="search-box-query"
            class="form-label"
        >
            <?= $this->escape($label) ?>
        </label>

        <input
            type="search"
            id="search-box-query"
            name="zoek"
            value="<?= $this->escape($value) ?>"
            placeholder="<?= $this->escape($placeholder) ?>"
            class="form-control"
        >
    </div>

    <div class="search-box__actions">
        <button
            type="submit"
            class="btn btn-primary"
        >
            <?= $this->escape($submitText) ?>
        </button>

        <?php if ($value !== '' && $clearUrl !== ''): ?>
            <a
                href="<?= $this->escape($clearUrl) ?>"
                class="btn btn-secondary"
            >
                Wissen
            </a>
        <?php endif; ?>
    </div>
</form>
