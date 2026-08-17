<?php

$type ??= 'primary';
$text ??= '';
$href ??= null;
$icon ??= null;
$buttonType ??= 'button';
$name ??= null;
$value ??= null;
$disabled ??= false;
$className ??= '';
$attributes ??= [];

$type = trim((string) $type);
$text = (string) $text;
$href = $href !== null
    ? trim((string) $href)
    : null;

$icon = $icon !== null
    ? trim((string) $icon)
    : null;

$buttonType = trim((string) $buttonType);
$className = trim((string) $className);

$allowedTypes = [
    'primary',
    'secondary',
    'danger',
    'success',
    'warning',
    'link',
];

if (!in_array($type, $allowedTypes, true)) {
    $type = 'primary';
}

$allowedButtonTypes = [
    'button',
    'submit',
    'reset',
];

if (!in_array($buttonType, $allowedButtonTypes, true)) {
    $buttonType = 'button';
}

$classes = array_filter([
    'btn',
    'btn-' . $type,
    $className,
]);

$class = implode(' ', $classes);

/**
 * @param array<string, scalar|bool|null> $values
 */
$renderAttributes = function (array $values): string {
    $rendered = [];

    foreach ($values as $attribute => $attributeValue) {
        $attribute = trim((string) $attribute);

        if ($attribute === '') {
            continue;
        }

        if ($attributeValue === false || $attributeValue === null) {
            continue;
        }

        if ($attributeValue === true) {
            $rendered[] = $this->escape($attribute);

            continue;
        }

        $rendered[] = sprintf(
            '%s="%s"',
            $this->escape($attribute),
            $this->escape((string) $attributeValue)
        );
    }

    if ($rendered === []) {
        return '';
    }

    return ' ' . implode(' ', $rendered);
};

$commonAttributes = is_array($attributes)
    ? $attributes
    : [];

$commonAttributes['class'] = $class;

if ($href !== null && $href !== '') {
    $commonAttributes['href'] = $href;
} else {
    $commonAttributes['type'] = $buttonType;

    if ($name !== null && trim((string) $name) !== '') {
        $commonAttributes['name'] = trim((string) $name);
    }

    if ($value !== null) {
        $commonAttributes['value'] = (string) $value;
    }

    if ((bool) $disabled) {
        $commonAttributes['disabled'] = true;
    }
}
?>

<?php if ($href !== null && $href !== ''): ?>
    <a<?= $renderAttributes($commonAttributes) ?>>
        <?php if ($icon !== null && $icon !== ''): ?>
            <span
                class="btn__icon icon icon-<?= $this->escape($icon) ?>"
                aria-hidden="true"
            ></span>
        <?php endif; ?>

        <span class="btn__text">
            <?= $this->escape($text) ?>
        </span>
    </a>
<?php else: ?>
    <button<?= $renderAttributes($commonAttributes) ?>>
        <?php if ($icon !== null && $icon !== ''): ?>
            <span
                class="btn__icon icon icon-<?= $this->escape($icon) ?>"
                aria-hidden="true"
            ></span>
        <?php endif; ?>

        <span class="btn__text">
            <?= $this->escape($text) ?>
        </span>
    </button>
<?php endif; ?>