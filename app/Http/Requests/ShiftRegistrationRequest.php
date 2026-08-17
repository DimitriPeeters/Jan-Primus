<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class ShiftRegistrationRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array{
     *     lid_id: int,
     *     status: string,
     *     opmerking_lid: ?string,
     *     annulatie_reden: ?string,
     *     aanwezig: bool
     * }
     */
    public function all(): array
    {
        return [
            'lid_id' => (int) ($this->input['lid_id'] ?? 0),
            'status' => trim(
                (string) ($this->input['status'] ?? '')
            ),
            'opmerking_lid' => $this->nullableString(
                $this->input['opmerking_lid'] ?? null
            ),
            'annulatie_reden' => $this->nullableString(
                $this->input['annulatie_reden'] ?? null
            ),
            'aanwezig' => $this->isChecked('aanwezig'),
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

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }
}
