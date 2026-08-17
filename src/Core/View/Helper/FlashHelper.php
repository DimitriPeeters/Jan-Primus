<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

use AEFS\Core\Session;
use AEFS\Core\View\Flash\FlashMessageBag;

final class FlashHelper
{
    private const TYPES = [
        'success',
        'error',
        'warning',
        'info',
    ];

    public function __construct(
        private readonly Session $session
    ) {
    }

    public function has(string $key): bool
    {
        return $this->session->hasFlash($key);
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->session->getFlash(
            $key,
            $default
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->session->allFlash();
    }

    public function messages(): FlashMessageBag
    {
        $bag = new FlashMessageBag();

        foreach (self::TYPES as $type) {
            $value = $this->get($type);

            if (is_string($value)) {
                $bag->add(
                    $type,
                    $value
                );

                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $message) {
                if (!is_string($message)) {
                    continue;
                }

                $bag->add(
                    $type,
                    $message
                );
            }
        }

        return $bag;
    }

    public function success(
        mixed $default = null
    ): mixed {
        return $this->get(
            'success',
            $default
        );
    }

    public function error(
        mixed $default = null
    ): mixed {
        return $this->get(
            'error',
            $default
        );
    }

    public function warning(
        mixed $default = null
    ): mixed {
        return $this->get(
            'warning',
            $default
        );
    }

    public function info(
        mixed $default = null
    ): mixed {
        return $this->get(
            'info',
            $default
        );
    }
}