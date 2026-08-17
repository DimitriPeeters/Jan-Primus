<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class MemberGroupRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array{naam: string, beschrijving: string}
     */
    public function all(): array
    {
        return [
            'naam' => trim(
                (string) ($this->input['naam'] ?? '')
            ),
            'beschrijving' => trim(
                (string) ($this->input['beschrijving'] ?? '')
            ),
        ];
    }
}
