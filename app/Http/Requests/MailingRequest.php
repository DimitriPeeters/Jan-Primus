<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class MailingRequest
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
     *     doelgroep_type: string,
     *     event_id: int,
     *     shift_id: int,
     *     shift_ids: int[],
     *     onderwerp: string,
     *     inhoud: string
     * }
     */
    public function all(): array
    {
        return [
            'doelgroep_type' => trim(
                (string) ($this->input['doelgroep_type'] ?? '')
            ),
            'event_id' => max(0, (int) ($this->input['event_id'] ?? 0)),
            'shift_id' => max(0, (int) ($this->input['shift_id'] ?? 0)),
            'shift_ids' => $this->integerList(
                $this->input['shift_ids'] ?? []
            ),
            'onderwerp' => trim(
                (string) ($this->input['onderwerp'] ?? '')
            ),
            'inhoud' => trim(
                (string) ($this->input['inhoud'] ?? '')
            ),
        ];
    }

    /**
     * @return int[]
     */
    private function integerList(mixed $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $value),
                    static fn(int $id): bool => $id > 0
                )
            )
        );
    }
}
