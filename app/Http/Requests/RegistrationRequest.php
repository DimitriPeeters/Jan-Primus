<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class RegistrationRequest
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
        $memberData = (new MemberRequest($this->input))->all();

        $memberData['password'] = (string) (
            $this->input['password'] ?? ''
        );

        $memberData['password_confirmation'] = (string) (
            $this->input['password_confirmation'] ?? ''
        );

        $memberData['actief'] = false;
        $memberData['opmerkingen'] = '';

        return $memberData;
    }
}