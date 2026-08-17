<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

use InvalidArgumentException;

final class MethodFieldHelper
{
    private const ALLOWED_METHODS = [
        'PUT',
        'PATCH',
        'DELETE',
    ];

    public function render(string $method): string
    {
        $method = strtoupper(trim($method));

        if (!in_array($method, self::ALLOWED_METHODS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'HTTP-methode [%s] kan niet worden gespoofd.',
                    $method
                )
            );
        }

        return sprintf(
            '<input type="hidden" name="_method" value="%s">',
            $method
        );
    }
}