<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftRegistration;

final class ReportService
{
    public function __construct(private readonly ShiftService $shiftService)
    {
    }

    /** @return Shift[] */
    public function shiftsForAttendance(): array
    {
        $shifts = $this->shiftService->allForAdministration();
        usort($shifts, static function (Shift $left, Shift $right): int {
            $dateComparison = strcmp($right->startOp, $left->startOp);

            return $dateComparison !== 0
                ? $dateComparison
                : $right->shiftId <=> $left->shiftId;
        });

        return $shifts;
    }

    /** @return array{shift: Shift, registrations: ShiftRegistration[]}|null */
    public function shiftAttendance(int $shiftId): ?array
    {
        if ($shiftId <= 0) {
            return null;
        }

        $shift = $this->shiftService->find($shiftId);
        if ($shift === null) {
            return null;
        }

        $registrations = array_values(array_filter(
            $this->shiftService->registrationsForShift($shiftId),
            static fn(ShiftRegistration $registration): bool =>
                $registration->isBevestigd()
        ));

        usort($registrations, static function (
            ShiftRegistration $left,
            ShiftRegistration $right
        ): int {
            $lastNameComparison = strnatcasecmp(
                trim((string) $left->lidAchternaam),
                trim((string) $right->lidAchternaam)
            );
            if ($lastNameComparison !== 0) {
                return $lastNameComparison;
            }

            $firstNameComparison = strnatcasecmp(
                trim((string) $left->lidVoornaam),
                trim((string) $right->lidVoornaam)
            );

            return $firstNameComparison !== 0
                ? $firstNameComparison
                : $left->lidId <=> $right->lidId;
        });

        return ['shift' => $shift, 'registrations' => $registrations];
    }
}
