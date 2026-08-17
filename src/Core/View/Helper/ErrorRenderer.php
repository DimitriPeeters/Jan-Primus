<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

final class ErrorRenderer
{
    public function field(
        ErrorBag $errors,
        string $field,
        string $class = 'form-error'
    ): string {
        if (!$errors->has($field)) {
            return '';
        }

        return sprintf(
            '<div class="%s">%s</div>',
            $this->escape($class),
            $this->escape($errors->first($field))
        );
    }

    public function list(
        ErrorBag $errors,
        string $class = 'error-list'
    ): string {
        if (!$errors->any()) {
            return '';
        }

        $html = sprintf(
            '<ul class="%s">',
            $this->escape($class)
        );

        foreach ($errors->flatten() as $message) {
            $html .= sprintf(
                '<li>%s</li>',
                $this->escape($message)
            );
        }

        $html .= '</ul>';

        return $html;
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