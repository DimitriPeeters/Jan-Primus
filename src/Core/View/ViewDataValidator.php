<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\View\Exception\InvalidViewDataException;

final class ViewDataValidator
{
    private const RESERVED_KEYS = [
        'this',
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_COOKIE',
        '_SESSION',
        '_REQUEST',
        '_ENV',
        '__file',
        '__data',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->validateKey($key);
        }
    }

    public function validateKey(string $key): void
    {
        if (
            $key === ''
            || preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key) !== 1
        ) {
            throw InvalidViewDataException::invalidVariableName($key);
        }

        if (in_array($key, self::RESERVED_KEYS, true)) {
            throw InvalidViewDataException::reservedVariable($key);
        }
    }
}