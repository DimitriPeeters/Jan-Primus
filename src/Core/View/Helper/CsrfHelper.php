<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

use AEFS\Core\Session;
use RuntimeException;
use Throwable;

final class CsrfHelper
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(
        private readonly Session $session
    ) {
    }

    public function token(): string
    {
        $token = $this->session->get(
            self::SESSION_KEY
        );

        if (is_string($token) && $token !== '') {
            return $token;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                'CSRF-token kon niet worden gegenereerd.',
                0,
                $throwable
            );
        }

        $this->session->put(
            self::SESSION_KEY,
            $token
        );

        return $token;
    }

    public function field(
        string $name = '_token'
    ): string {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $this->escape($name),
            $this->escape($this->token())
        );
    }

    public function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        return hash_equals(
            $this->token(),
            $token
        );
    }

    public function regenerate(): string
    {
        $this->session->forget(
            self::SESSION_KEY
        );

        return $this->token();
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