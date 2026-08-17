<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class EventCancellationRequest
{
    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly array $input
    ) {
    }

    /**
     * @return array{reden: ?string}
     */
    public function all(): array
    {
        $reason = trim(
            (string) ($this->input['annulatie_reden'] ?? '')
        );

        return [
            'reden' => $reason !== '' ? $reason : null,
        ];
    }
}
