<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftRegistration;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Repositories\DailyAttendanceRepository;
use DateTimeImmutable;

final class ReportService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly DailyAttendanceRepository $dailyAttendance,
        private readonly EventService $eventService
    ) {
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

    /**
     * @return array<int, array{date: string, label: string, assignments: int}>
     */
    public function daysForAttendance(): array
    {
        $days = [];

        foreach ($this->shiftService->allForAdministration() as $shift) {
            $date = substr($shift->startOp, 0, 10);
            $days[$date] ??= [
                'date' => $date,
                'label' => (new DateTimeImmutable($date))->format('d/m/Y'),
                'assignments' => 0,
            ];
            $days[$date]['assignments'] += $shift->aantalBevestigd;
        }

        krsort($days);

        return array_values($days);
    }

    /**
     * @return array{
     *   date: string,
     *   displayDate: string,
     *   people: array<int, array{
     *     memberId: int,
     *     firstName: string,
     *     lastName: string,
     *     assignments: array<int, array{registration: ShiftRegistration, shift: Shift}>,
     *     walkieNumber: string,
     *     earpiece: bool
     *   }>,
     *   assignmentCount: int
     * }|null
     */
    public function dayAttendance(string $date): ?array
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return null;
        }

        $people = [];
        $assignmentCount = 0;
        $hasShift = false;

        foreach ($this->shiftService->allForAdministration() as $shift) {
            if (substr($shift->startOp, 0, 10) !== $date) {
                continue;
            }

            $hasShift = true;

            foreach ($this->shiftService->registrationsForShift($shift->shiftId) as $registration) {
                if (!$registration->isBevestigd()) {
                    continue;
                }

                $people[$registration->lidId] ??= [
                    'memberId' => $registration->lidId,
                    'firstName' => trim((string) $registration->lidVoornaam),
                    'lastName' => trim((string) $registration->lidAchternaam),
                    'assignments' => [],
                ];
                $people[$registration->lidId]['assignments'][] = [
                    'registration' => $registration,
                    'shift' => $shift,
                ];
                $assignmentCount++;
            }
        }

        if (!$hasShift) {
            return null;
        }

        $dailyDetails = $this->dailyAttendance->forDate($date);

        foreach ($people as $memberId => &$person) {
            $details = $dailyDetails[$memberId] ?? [
                'walkieNumber' => '',
                'earpiece' => false,
            ];
            $person['walkieNumber'] = $details['walkieNumber'];
            $person['earpiece'] = $details['earpiece'];
        }
        unset($person);

        $people = array_values($people);
        usort($people, static function (array $left, array $right): int {
            $lastName = strnatcasecmp($left['lastName'], $right['lastName']);

            if ($lastName !== 0) {
                return $lastName;
            }

            $firstName = strnatcasecmp($left['firstName'], $right['firstName']);

            return $firstName !== 0
                ? $firstName
                : $left['memberId'] <=> $right['memberId'];
        });

        foreach ($people as &$person) {
            usort(
                $person['assignments'],
                static fn(array $left, array $right): int =>
                    strcmp($left['shift']->startOp, $right['shift']->startOp)
            );
        }
        unset($person);

        return [
            'date' => $date,
            'displayDate' => $parsed->format('d/m/Y'),
            'people' => $people,
            'assignmentCount' => $assignmentCount,
        ];
    }

    public function saveDayDetails(
        string $date,
        int $memberId,
        string $walkieNumber,
        bool $earpiece
    ): void {
        if ($this->dayAttendance($date) === null) {
            throw new \InvalidArgumentException('De gekozen eventdag bestaat niet.');
        }

        if ($memberId <= 0) {
            throw new \InvalidArgumentException('Het gekozen lid is ongeldig.');
        }

        $walkieNumber = trim($walkieNumber);

        if (mb_strlen($walkieNumber) > 10) {
            throw new \InvalidArgumentException('Nummer walkie mag maximaal 10 karakters bevatten.');
        }

        $report = $this->dayAttendance($date);
        $memberIsListed = false;

        foreach ($report['people'] as $person) {
            if ($person['memberId'] === $memberId) {
                $memberIsListed = true;
                break;
            }
        }

        if (!$memberIsListed) {
            throw new \InvalidArgumentException('Dit lid staat niet op de gekozen eventdag.');
        }

        $this->dailyAttendance->upsert(
            $date,
            $memberId,
            $walkieNumber,
            $earpiece
        );
    }

    /** @return Event[] */
    public function eventsForParticipantList(): array
    {
        $events = $this->eventService->allForAdministration();
        usort(
            $events,
            static fn(Event $left, Event $right): int =>
                strcmp($right->startDatum, $left->startDatum)
                ?: $right->eventId <=> $left->eventId
        );

        return $events;
    }

    /**
     * @return array{event: Event, registrations: EventRegistration[]}|null
     */
    public function eventParticipants(int $eventId): ?array
    {
        if ($eventId <= 0) {
            return null;
        }

        $event = $this->eventService->find($eventId);

        if ($event === null) {
            return null;
        }

        $registrations = array_values(array_filter(
            $this->eventService->registrationsForEvent($eventId),
            static fn(EventRegistration $registration): bool =>
                $registration->isActief()
        ));

        usort($registrations, static function (
            EventRegistration $left,
            EventRegistration $right
        ): int {
            $lastName = strnatcasecmp(
                trim((string) $left->lidAchternaam),
                trim((string) $right->lidAchternaam)
            );

            if ($lastName !== 0) {
                return $lastName;
            }

            $firstName = strnatcasecmp(
                trim((string) $left->lidVoornaam),
                trim((string) $right->lidVoornaam)
            );

            return $firstName !== 0
                ? $firstName
                : $left->lidId <=> $right->lidId;
        });

        return ['event' => $event, 'registrations' => $registrations];
    }
}
