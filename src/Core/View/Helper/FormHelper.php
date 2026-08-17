<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

final class FormHelper
{
    public function __construct(
        private readonly CsrfHelper $csrf,
        private readonly MethodFieldHelper $methodField
    ) {
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function open(
        string $action,
        string $method = 'POST',
        array $attributes = []
    ): string {
        $method = strtoupper(trim($method));

        if ($method === '') {
            $method = 'POST';
        }

        $formMethod = in_array($method, ['GET', 'POST'], true)
            ? $method
            : 'POST';

        $attributes = array_replace(
            [
                'action' => $action,
                'method' => strtolower($formMethod),
            ],
            $attributes
        );

        $html = '<form' . $this->attributes($attributes) . '>';

        if ($formMethod === 'POST') {
            $html .= $this->csrf->field();
        }

        if (!in_array($method, ['GET', 'POST'], true)) {
            $html .= $this->methodField->render($method);
        }

        return $html;
    }

    public function close(): string
    {
        return '</form>';
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function input(
        string $type,
        string $name,
        mixed $value = null,
        array $attributes = []
    ): string {
        $attributes = array_replace(
            [
                'type' => $type,
                'name' => $name,
            ],
            $attributes
        );

        if ($value !== null) {
            $attributes['value'] = $value;
        }

        return '<input' . $this->attributes($attributes) . '>';
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function text(
        string $name,
        mixed $value = null,
        array $attributes = []
    ): string {
        return $this->input('text', $name, $value, $attributes);
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function email(
        string $name,
        mixed $value = null,
        array $attributes = []
    ): string {
        return $this->input('email', $name, $value, $attributes);
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function password(
        string $name,
        array $attributes = []
    ): string {
        unset($attributes['value']);

        return $this->input('password', $name, null, $attributes);
    }

    public function hidden(
        string $name,
        mixed $value
    ): string {
        return $this->input('hidden', $name, $value);
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function textarea(
        string $name,
        mixed $value = null,
        array $attributes = []
    ): string {
        $attributes = array_replace(
            ['name' => $name],
            $attributes
        );

        return sprintf(
            '<textarea%s>%s</textarea>',
            $this->attributes($attributes),
            $this->escape((string) ($value ?? ''))
        );
    }

    /**
     * @param array<string, scalar> $options
     * @param array<string, scalar|bool|null> $attributes
     */
    public function select(
        string $name,
        array $options,
        mixed $selected = null,
        array $attributes = []
    ): string {
        $attributes = array_replace(
            ['name' => $name],
            $attributes
        );

        $html = '<select' . $this->attributes($attributes) . '>';

        foreach ($options as $value => $label) {
            $html .= sprintf(
                '<option%s>%s</option>',
                $this->attributes([
                    'value' => $value,
                    'selected' => (string) $value === (string) $selected,
                ]),
                $this->escape((string) $label)
            );
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function checkbox(
        string $name,
        mixed $value = 1,
        bool $checked = false,
        array $attributes = []
    ): string {
        $attributes['checked'] = $checked;

        return $this->input(
            'checkbox',
            $name,
            $value,
            $attributes
        );
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function radio(
        string $name,
        mixed $value,
        bool $checked = false,
        array $attributes = []
    ): string {
        $attributes['checked'] = $checked;

        return $this->input(
            'radio',
            $name,
            $value,
            $attributes
        );
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function label(
        string $for,
        string $text,
        array $attributes = []
    ): string {
        $attributes = array_replace(
            ['for' => $for],
            $attributes
        );

        return sprintf(
            '<label%s>%s</label>',
            $this->attributes($attributes),
            $this->escape($text)
        );
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function button(
        string $text,
        string $type = 'submit',
        array $attributes = []
    ): string {
        $attributes = array_replace(
            ['type' => $type],
            $attributes
        );

        return sprintf(
            '<button%s>%s</button>',
            $this->attributes($attributes),
            $this->escape($text)
        );
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function attributes(array $attributes): string
    {
        return (new HtmlAttributes($attributes))->toHtml();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}