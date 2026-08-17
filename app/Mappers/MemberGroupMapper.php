<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Models\MemberGroup;

final class MemberGroupMapper
{
    /**
     * @param array<string, mixed> $row
     */
    public function fromDatabase(array $row): MemberGroup
    {
        $description = trim(
            (string) ($row['beschrijving'] ?? '')
        );

        return new MemberGroup(
            groepId: (int) ($row['groep_id'] ?? 0),
            naam: trim((string) ($row['naam'] ?? '')),
            beschrijving: $description !== ''
                ? $description
                : null,
            ledenAantal: (int) ($row['leden_aantal'] ?? 0)
        );
    }
}
