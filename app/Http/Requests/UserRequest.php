<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class UserRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array{rol: string, actief: bool, mail_blacklist: bool}
     */
    public function all(): array
    {
        return [
            'rol' => trim(
                (string) ($this->input['rol'] ?? '')
            ),
            'actief' => $this->isChecked('actief'),
            'mail_blacklist' => $this->isChecked('mail_blacklist'),
        ];
    }

    private function isChecked(string $key): bool
    {
        if (!array_key_exists($key, $this->input)) {
            return false;
        }

        return filter_var(
            $this->input[$key],
            FILTER_VALIDATE_BOOL
        );
    }
}
