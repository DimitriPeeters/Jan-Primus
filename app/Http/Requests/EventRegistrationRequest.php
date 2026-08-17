<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\BelgianDateTime;

final class EventRegistrationRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array{dagen: string[]}
     */
    public function all(): array
    {
        $inputDays = $this->input['dagen'] ?? [];

        if (!is_array($inputDays)) {
            $inputDays = [];
        }

        $dagen = [];

        foreach ($inputDays as $inputDay) {
            $datum = BelgianDateTime::normalizeDateInput($inputDay);

            if ($datum !== '') {
                $dagen[] = $datum;
            }
        }

        $dagen = array_values(array_unique($dagen));
        sort($dagen);

        return [
            'dagen' => $dagen,
        ];
    }
}
