<?php

declare(strict_types=1);

namespace App\Models;

final class MemberGroup
{
    public function __construct(
        public readonly int $groepId,
        public readonly string $naam,
        public readonly ?string $beschrijving,
        public readonly int $ledenAantal = 0
    ) {
    }
}
