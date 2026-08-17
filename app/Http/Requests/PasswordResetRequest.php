<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class PasswordResetRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    public function email(): string
    {
        return strtolower(
            trim((string) ($this->input['email'] ?? ''))
        );
    }

    /**
     * @return array{password: string, password_confirmation: string}
     */
    public function passwords(): array
    {
        return [
            'password' => (string) ($this->input['password'] ?? ''),
            'password_confirmation' => (string) (
                $this->input['password_confirmation'] ?? ''
            ),
        ];
    }
}
