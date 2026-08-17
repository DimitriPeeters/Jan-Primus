<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

use AEFS\Core\Session;

final class OldInputHelper
{
    private const FLASH_KEY = '_old_input';

    public function __construct(
        private readonly Session $session
    ) {
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $input = $this->all();

        return $input[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->all()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $input = $this->session->getFlash(
            self::FLASH_KEY,
            []
        );

        return is_array($input)
            ? $input
            : [];
    }
}