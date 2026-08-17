<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ShiftType;

final class ShiftTypeRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'naam' => trim((string) ($this->input['naam'] ?? '')),
            'kleur' => trim(
                (string) (
                    $this->input['kleur']
                    ?? ShiftType::DEFAULT_COLOR
                )
            ),
            'icoon' => trim((string) ($this->input['icoon'] ?? '')),
            'omschrijving' => trim(
                (string) ($this->input['omschrijving'] ?? '')
            ),
            'actief' => array_key_exists('actief', $this->input)
                && filter_var(
                    $this->input['actief'],
                    FILTER_VALIDATE_BOOL
                ),
        ];
    }
}
