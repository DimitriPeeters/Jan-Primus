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
            'default_shift_compensation' => $this->amount(
                'default_shift_compensation'
            ),
            'group_supplement' => $this->amount(
                'group_supplement'
            ),
            'default_event_uses_groups' => $this->checked(
                'default_event_uses_groups'
            ) ? '1' : '0',
        ];
    }

    private function text(string $key): string
    {
        return trim((string) ($this->input[$key] ?? ''));
    }

    private function amount(string $key): string
    {
        $value = str_replace(
            ['€', ' '],
            '',
            $this->text($key)
        );

        if (
            preg_match('/^\d+(?:[,.]\d{1,2})?$/', $value) !== 1
        ) {
            return $value;
        }

        return number_format(
            (float) str_replace(',', '.', $value),
            2,
            '.',
            ''
        );
    }

    private function checked(string $key): bool
    {
        return array_key_exists($key, $this->input)
            && filter_var(
                $this->input[$key],
                FILTER_VALIDATE_BOOL
            );
    }
}
