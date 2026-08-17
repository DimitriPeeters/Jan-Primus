<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Shift;
use App\Models\ShiftRegistration;
use App\Repositories\ReportRepository;

final class ReportService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly EventService $eventService,
        private readonly ReportRepository $reportRepository,
        private readonly EncryptionService $encryption
    ) {
    }

    /**
     * @return Shift[]
     */
    public function shiftsForAttendance(): array
    {
        $shifts = $this->shiftService->allForAdministration();

        usort(
            $shifts,
            static function (Shift $left, Shift $right): int {
                $dateComparison = strcmp(
                    $right->startOp,
                    $left->startOp
                );

                return $dateComparison !== 0
                    ? $dateComparison
                    : $right->shiftId <=> $left->shiftId;
            }
        );

        return $shifts;
    }

    /**
     * @return array{
     *     shift: Shift,
     *     registrations: ShiftRegistration[]
     * }|null
     */
    public function shiftAttendance(int $shiftId): ?array
    {
        if ($shiftId <= 0) {
            return null;
        }

        $shift = $this->shiftService->find($shiftId);

        if ($shift === null) {
            return null;
        }

        $registrations = array_values(
            array_filter(
                $this->shiftService->registrationsForShift($shiftId),
                static fn(
                    ShiftRegistration $registration
                ): bool => $registration->isBevestigd()
            )
        );

        usort(
            $registrations,
            static function (
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
            }
        );

        return [
            'shift' => $shift,
            'registrations' => $registrations,
        ];
    }

    /**
     * @return Event[]
     */
    public function eventsForCompensation(): array
    {
        $events = $this->eventService->allForAdministration();

        usort(
            $events,
            static function (Event $left, Event $right): int {
                $dateComparison = strcmp(
                    $right->startDatum,
                    $left->startDatum
                );

                return $dateComparison !== 0
                    ? $dateComparison
                    : $right->eventId <=> $left->eventId;
            }
        );

        return $events;
    }

    /**
     * @return array{
     *     event: Event,
     *     dates: string[],
     *     sections: array<int, array<string, mixed>>,
     *     total_cents: int,
     *     shift_count: int,
     *     group_supplement_cents: int,
     *     group_options: array<int, array{key: string, label: string}>,
     *     selected_group_key: ?string,
     *     selected_group_label: ?string
     * }|null
     */
    public function eventCompensation(
        int $eventId,
        ?string $groupKey = null,
        bool $includeBankAccounts = false
    ): ?array {
        if ($eventId <= 0) {
            return null;
        }

        $event = $this->eventService->find($eventId);

        if ($event === null) {
            return null;
        }

        $dates = $event->dates();
        $sections = [];
        $totalCents = 0;
        $shiftCount = 0;
        $groupSupplementCents = $this->amountToCents(
            $event->groepstoeslagBedrag
        );

        foreach (
            $this->reportRepository
                ->workedShiftCompensations($eventId) as $row
        ) {
            $date = $row['werkdatum'];

            if (!in_array($date, $dates, true)) {
                continue;
            }

            $groupId = $event->werktMetGroepen
                ? $row['groep_id']
                : null;
            $sectionKey = $this->sectionKey(
                $event,
                $groupId
            );
            $sectionLabel = $this->sectionLabel(
                $event,
                $row['groep_naam']
            );

            if (!isset($sections[$sectionKey])) {
                $sections[$sectionKey] = [
                    'key' => $sectionKey,
                    'label' => $sectionLabel,
                    'is_group' => $event->werktMetGroepen
                        && $groupId !== null,
                    'members' => [],
                    'day_totals' => array_fill_keys($dates, 0),
                    'total_cents' => 0,
                    'shift_count' => 0,
                ];
            }

            $memberId = $row['lid_id'];

            if (!isset($sections[$sectionKey]['members'][$memberId])) {
                $sections[$sectionKey]['members'][$memberId] = [
                    'member_id' => $memberId,
                    'first_name' => $row['voornaam'],
                    'last_name' => $row['achternaam'],
                    'bank_account' => $includeBankAccounts
                        ? $this->encryption->decrypt(
                            $row['rekeningnummer']
                        )
                        : null,
                    'days' => array_fill_keys(
                        $dates,
                        [
                            'amount_cents' => 0,
                            'shift_count' => 0,
                        ]
                    ),
                    'total_cents' => 0,
                    'shift_count' => 0,
                ];
            }

            $amountCents = $this->amountToCents(
                $row['vergoeding_bedrag']
            );

            if ($event->werktMetGroepen && $groupId !== null) {
                $amountCents += $groupSupplementCents;
            }

            $member = &$sections[$sectionKey]['members'][$memberId];
            $member['days'][$date]['amount_cents'] += $amountCents;
            $member['days'][$date]['shift_count']++;
            $member['total_cents'] += $amountCents;
            $member['shift_count']++;
            unset($member);

            $sections[$sectionKey]['day_totals'][$date] += $amountCents;
            $sections[$sectionKey]['total_cents'] += $amountCents;
            $sections[$sectionKey]['shift_count']++;
            $totalCents += $amountCents;
            $shiftCount++;
        }

        foreach ($sections as &$section) {
            $section['members'] = array_values(
                $section['members']
            );

            usort(
                $section['members'],
                static function (array $left, array $right): int {
                    $lastNameComparison = strnatcasecmp(
                        $left['last_name'],
                        $right['last_name']
                    );

                    return $lastNameComparison !== 0
                        ? $lastNameComparison
                        : strnatcasecmp(
                            $left['first_name'],
                            $right['first_name']
                        );
                }
            );
        }
        unset($section);

        uasort(
            $sections,
            static function (array $left, array $right): int {
                if ($left['is_group'] !== $right['is_group']) {
                    return $left['is_group'] ? -1 : 1;
                }

                return strnatcasecmp(
                    $left['label'],
                    $right['label']
                );
            }
        );

        $groupOptions = [];

        if ($event->werktMetGroepen) {
            foreach ($sections as $key => $section) {
                $groupOptions[] = [
                    'key' => $key,
                    'label' => (string) $section['label'],
                ];
            }
        }

        $selectedGroupKey = null;
        $selectedGroupLabel = null;

        if (
            $event->werktMetGroepen
            && $groupKey !== null
            && isset($sections[$groupKey])
        ) {
            $selectedGroupKey = $groupKey;
            $selectedGroupLabel = (string) $sections[$groupKey]['label'];
            $selectedSection = $sections[$groupKey];
            $sections = [$groupKey => $selectedSection];
            $totalCents = (int) $selectedSection['total_cents'];
            $shiftCount = (int) $selectedSection['shift_count'];
        }

        return [
            'event' => $event,
            'dates' => $dates,
            'sections' => array_values($sections),
            'total_cents' => $totalCents,
            'shift_count' => $shiftCount,
            'group_supplement_cents' => $groupSupplementCents,
            'group_options' => $groupOptions,
            'selected_group_key' => $selectedGroupKey,
            'selected_group_label' => $selectedGroupLabel,
        ];
    }

    private function sectionKey(
        Event $event,
        ?int $groupId
    ): string {
        if (!$event->werktMetGroepen) {
            return 'individual';
        }

        return $groupId !== null
            ? 'group:' . $groupId
            : 'ungrouped';
    }

    private function sectionLabel(
        Event $event,
        ?string $groupName
    ): string {
        if (!$event->werktMetGroepen) {
            return 'Vrijwilligers';
        }

        return $groupName !== null && trim($groupName) !== ''
            ? $groupName
            : 'Zonder groep';
    }

    private function amountToCents(string $amount): int
    {
        if (preg_match('/^\d+\.\d{2}$/', $amount) === 1) {
            return (int) str_replace('.', '', $amount);
        }

        return (int) round((float) $amount * 100);
    }
}
