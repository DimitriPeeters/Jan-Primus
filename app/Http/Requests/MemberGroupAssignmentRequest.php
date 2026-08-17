<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class MemberGroupAssignmentRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return int[]
     */
    public function memberIds(): array
    {
        $values = $this->input['lid_ids'] ?? [];

        if (!is_array($values)) {
            $values = [$values];
        }

        $memberIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $value): int => (int) $value,
                        $values
                    ),
                    static fn (int $value): bool => $value > 0
                )
            )
        );

        sort($memberIds);

        return $memberIds;
    }
}
