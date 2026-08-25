<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class SettingsRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return [
            'application_name' => $this->text('application_name'),
            'organization_name' => $this->text('organization_name'),
            'mail_from_name' => $this->text('mail_from_name'),
            'mail_reply_to_name' => $this->text('mail_reply_to_name'),
            'mail_reply_to_address' => strtolower(
                $this->text('mail_reply_to_address')
            ),
        ];
    }

    private function text(string $key): string
    {
        return trim((string) ($this->input[$key] ?? ''));
    }

}
