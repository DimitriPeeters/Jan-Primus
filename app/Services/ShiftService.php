<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Database;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Shift;
use App\Models\ShiftRegistration;
use App\Models\ShiftType;
use App\Repositories\EventRepository;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\ShiftRegistrationRepository;
use App\Repositories\ShiftRepository;
use App\Repositories\ShiftTypeRepository;
use App\Validators\ShiftRegistrationValidator;
use App\Validators\ShiftValidator;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use RuntimeException;

final class ShiftService
{
    public function __construct(
        private readonly Database $database,
        private readonly ShiftRepository $shiftRepository,
        private readonly ShiftRegistrationRepository $registrationRepository,
        private readonly ShiftTypeRepository $typeRepository,
        private readonly EventRepository $eventRepository,
        private readonly EventRegistrationRepository $eventRegistrationRepository,
        private readonly ShiftValidator $shiftValidator,
        private readonly ShiftRegistrationValidator $registrationValidator,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @return Shift[]
     */
    public function allForAdministration(): array
    {
        return $this->shiftRepository->allForAdministration();
    }

    /**
     * @return Shift[]
     */
    public function visibleToMembers(): array
    {
        return $this->shiftRepository->visibleToMembers();
    }

    /**
     * @return Shift[]
     */
    public function visibleToMember(int $memberId): array
    {
        if ($memberId <= 0) {
            return [];
        }

        return array_values(
            array_filter(
                $this->visibleToMembers(),
                fn (Shift $shift): bool => $this->memberHasEventDay(
                    $shift,
                    $memberId
                )
            )
        );
    }

    /**
     * @return Shift[]
     */
    public function findByEvent(
        int $eventId,
        bool $includeCancelled = true
    ): array {
        if ($eventId <= 0) {
            return [];
        }

        return $this->shiftRepository->findByEvent(
            $eventId,
            $includeCancelled
        );
    }

    public function find(int $id): ?Shift
    {
        if ($id <= 0) {
            return null;
        }

        return $this->shiftRepository->find($id);
    }

    public function findVisibleToMembers(int $id): ?Shift
    {
        if ($id <= 0) {
            return null;
        }

        return $this->shiftRepository->findVisibleToMembers($id);
    }

    /**
     * @return ShiftType[]
     */
    public function allTypes(): array
    {
        return $this->typeRepository->all();
    }

    /**
     * @return ShiftType[]
     */
    public function activeTypes(): array
    {
        $this->typeRepository->ensureDefault();

        return $this->typeRepository->active();
    }

    /**
     * @return ShiftRegistration[]
     */
    public function registrationsForShift(int $shiftId): array
    {
        if ($shiftId <= 0) {
            return [];
        }

        return $this->registrationRepository->findByShift($shiftId);
    }

    /**
     * @return ShiftRegistration[]
     */
    public function registrationsForMember(int $memberId): array
    {
        if ($memberId <= 0) {
            return [];
        }

        return $this->registrationRepository->findByMember($memberId);
    }

    /**
     * @return ShiftRegistration[]
     */
    public function pendingRegistrations(): array
    {
        return $this->registrationRepository->findPending();
    }

    public function findRegistration(
        int $registrationId
    ): ?ShiftRegistration {
        if ($registrationId <= 0) {
            return null;
        }

        return $this->registrationRepository->find($registrationId);
    }

    public function findMemberRegistration(
        int $shiftId,
        int $memberId
    ): ?ShiftRegistration {
        if ($shiftId <= 0 || $memberId <= 0) {
            return null;
        }

        return $this->registrationRepository->findByShiftAndMember(
            $shiftId,
            $memberId
        );
    }

    /**
     * @return EventRegistration[]
     */
    public function eligibleEventRegistrationsForShift(
        int $shiftId
    ): array {
        $shift = $this->find($shiftId);

        if ($shift === null) {
            return [];
        }

        $shiftDate = (new DateTimeImmutable($shift->startOp))
            ->format('Y-m-d');

        return $this->eventRegistrationRepository
            ->findEligibleForShift(
                $shift->eventId,
                $shiftId,
                $shiftDate
            );
    }

    public function assignByAdmin(
        int $shiftId,
        int $memberId,
        string $status
    ): int {
        if ($shiftId <= 0 || $memberId <= 0) {
            throw new InvalidArgumentException(
                'Kies een geldige vrijwilliger en shift.'
            );
        }

        if (!in_array(
            $status,
            [
                ShiftRegistration::STATUS_BEVESTIGD,
                ShiftRegistration::STATUS_RESERVE,
            ],
            true
        )) {
            throw new InvalidArgumentException(
                'Kies bevestigd of reserve als toewijzingsstatus.'
            );
        }

        return $this->database->transaction(
            function () use ($shiftId, $memberId, $status): int {
                $shift = $this->shiftRepository->lockForUpdate($shiftId);

                if ($shift === null) {
                    throw new InvalidArgumentException(
                        'Shift niet gevonden.'
                    );
                }

                if (!$shift->isActief()) {
                    throw new DomainException(
                        'Aan een geannuleerde shift kan niemand worden toegewezen.'
                    );
                }

                if (
                    new DateTimeImmutable($shift->startOp)
                    <= new DateTimeImmutable()
                ) {
                    throw new DomainException(
                        'Deze shift is al gestart.'
                    );
                }

                $event = $this->eventRepository->lockForUpdate(
                    $shift->eventId
                );

                if ($event === null) {
                    throw new InvalidArgumentException(
                        'Evenement niet gevonden.'
                    );
                }

                if ($event->isCancelled()) {
                    throw new DomainException(
                        'Aan een geannuleerd evenement kan niemand meer aan een shift worden toegewezen.'
                    );
                }

                $eventRegistration = $this->eventRegistrationRepository
                    ->findByEventAndMember(
                        $shift->eventId,
                        $memberId
                    );

                if ($eventRegistration === null) {
                    throw new DomainException(
                        'Dit lid is niet voor het evenement ingeschreven.'
                    );
                }

                $eventRegistration = $this->eventRegistrationRepository
                    ->findForUpdate(
                        $eventRegistration->inschrijvingId
                    );

                if (
                    $eventRegistration === null
                    || !$eventRegistration->isActief()
                ) {
                    throw new DomainException(
                        'Alleen actieve evenementdeelnemers kunnen aan een shift worden toegewezen.'
                    );
                }

                if ($eventRegistration->hasPendingCancellation()) {
                    throw new DomainException(
                        'Dit lid heeft een openstaande annulatieaanvraag en kan niet aan een shift worden toegewezen.'
                    );
                }

                $shiftDate = (new DateTimeImmutable($shift->startOp))
                    ->format('Y-m-d');

                if (!$eventRegistration->coversDate($shiftDate)) {
                    throw new DomainException(
                        'Dit lid heeft de dag van deze shift niet als beschikbaar opgegeven.'
                    );
                }

                $this->assertNoScheduleOverlap(
                    $memberId,
                    $shift->startOp,
                    $shift->eindOp,
                    $shift->shiftId
                );

                $existing = $this->registrationRepository
                    ->findByShiftAndMember($shiftId, $memberId);

                if (
                    $status === ShiftRegistration::STATUS_BEVESTIGD
                    && $this->registrationRepository->countByStatus(
                        $shiftId,
                        ShiftRegistration::STATUS_BEVESTIGD
                    ) >= $shift->maxPersonen
                ) {
                    throw new DomainException(
                        'Deze shift heeft geen vrije bevestigde plaatsen meer.'
                    );
                }

                $userId = $this->requireAuthenticatedUserId();
                $registrationId = $this->registrationRepository->assign(
                    $shiftId,
                    $memberId,
                    $status,
                    $userId
                );

                $registration = $this->registrationRepository->find(
                    $registrationId
                );

                if ($registration === null) {
                    throw new RuntimeException(
                        'De shifttoewijzing kon niet worden geladen.'
                    );
                }

                if ($existing === null) {
                    $this->auditLog->created(
                        entity: 'shift_registration',
                        id: $registrationId,
                        userId: $userId,
                        values: $registration->toAuditArray()
                    );
                } else {
                    $this->auditLog->updated(
                        entity: 'shift_registration',
                        id: $registrationId,
                        userId: $userId,
                        oldValues: $existing->toAuditArray(),
                        newValues: $registration->toAuditArray()
                    );
                }

                return $registrationId;
            }
        );
    }

    public function canMemberChooseShift(
        int $shiftId,
        int $memberId
    ): bool {
        $shift = $this->findVisibleToMembers($shiftId);

        return $shift !== null
            && !$shift->isAfgelopen()
            && $this->memberHasEventDay($shift, $memberId);
    }

    public function registerByMember(
        int $shiftId,
        int $memberId,
        ?string $comment = null
    ): int {
        if ($shiftId <= 0 || $memberId <= 0) {
            throw new InvalidArgumentException('Ongeldige shiftinschrijving.');
        }

        $comment = $comment !== null ? trim($comment) : null;
        $comment = $comment === '' ? null : $comment;

        return $this->database->transaction(
            function () use ($shiftId, $memberId, $comment): int {
                $shift = $this->shiftRepository->lockForUpdate($shiftId);

                if (
                    $shift === null
                    || !$shift->isActief()
                    || $shift->eventStatus !== Event::STATUS_PUBLISHED
                ) {
                    throw new DomainException(
                        'Deze shift is niet beschikbaar voor zelfinschrijving.'
                    );
                }

                if ($shift->isAfgelopen()) {
                    throw new DomainException('Deze shift is al afgelopen.');
                }

                if (!$this->memberHasEventDay($shift, $memberId)) {
                    throw new DomainException(
                        'Je bent voor dit evenement of deze eventdag niet ingeschreven.'
                    );
                }

                $this->assertNoScheduleOverlap(
                    $memberId,
                    $shift->startOp,
                    $shift->eindOp,
                    $shift->shiftId
                );

                $existing = $this->registrationRepository
                    ->findByShiftAndMember($shiftId, $memberId);

                if ($existing !== null && $existing->isActief()) {
                    throw new DomainException(
                        'Je hebt voor deze shift al een actieve keuze of toewijzing.'
                    );
                }

                $registrationId = $this->registrationRepository
                    ->submitByMember($shiftId, $memberId, $comment);
                $registration = $this->registrationRepository
                    ->find($registrationId);

                if ($registration === null) {
                    throw new RuntimeException(
                        'De shiftinschrijving kon niet worden geladen.'
                    );
                }

                if ($existing === null) {
                    $this->auditLog->created(
                        'shift_registration',
                        $registrationId,
                        Auth::id(),
                        $registration->toAuditArray()
                    );
                } else {
                    $this->auditLog->updated(
                        'shift_registration',
                        $registrationId,
                        Auth::id(),
                        $existing->toAuditArray(),
                        $registration->toAuditArray()
                    );
                }

                return $registrationId;
            }
        );
    }

    public function withdrawByMember(int $shiftId, int $memberId): void
    {
        $registration = $this->findMemberRegistration($shiftId, $memberId);

        if ($registration === null || !$registration->isActief()) {
            throw new InvalidArgumentException(
                'Er is geen actieve shiftkeuze om in te trekken.'
            );
        }

        $this->cancelRegistration(
            $registration->inschrijvingId,
            'Ingetrokken door het lid.'
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data['status'] = Shift::STATUS_ACTIEF;

        $event = $this->requireEvent(
            (int) ($data['event_id'] ?? 0)
        );

        $type = $this->requireType(
            (int) ($data['type_id'] ?? 0)
        );

        if (!$type->isActief()) {
            throw new DomainException(
                'Het gekozen shifttype is niet actief.'
            );
        }

        $this->shiftValidator->validateForEvent(
            $data,
            $event
        );

        return $this->database->transaction(
            function () use ($data): int {
                $id = $this->shiftRepository->create($data);

                $this->auditLog->created(
                    entity: 'shift',
                    id: $id,
                    userId: Auth::id(),
                    values: $data
                );

                return $id;
            }
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $id,
        array $data
    ): void {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shift.'
            );
        }

        $this->database->transaction(
            function () use ($id, $data): void {
                $shift = $this->shiftRepository->lockForUpdate($id);

                if ($shift === null) {
                    throw new InvalidArgumentException(
                        'Shift niet gevonden.'
                    );
                }

                $requestedStatus = (string) (
                    $data['status'] ?? $shift->status
                );

                if ($requestedStatus !== $shift->status) {
                    throw new DomainException(
                        'Wijzig de status niet via het formulier. Gebruik de afzonderlijke annuleeractie.'
                    );
                }

                $data['status'] = $shift->status;

                $event = $this->requireEvent(
                    (int) ($data['event_id'] ?? 0)
                );

                $type = $this->requireType(
                    (int) ($data['type_id'] ?? 0)
                );

                if (
                    !$type->isActief()
                    && $type->typeId !== $shift->typeId
                ) {
                    throw new DomainException(
                        'Het gekozen shifttype is niet actief.'
                    );
                }

                $this->shiftValidator->validateForEvent(
                    $data,
                    $event
                );

                $registrationCount = $this->shiftRepository
                    ->countRegistrations($id);

                if (
                    $registrationCount > 0
                    && (int) $data['event_id'] !== $shift->eventId
                ) {
                    throw new DomainException(
                        'Een shift met inschrijvingen kan niet naar een ander evenement worden verplaatst.'
                    );
                }

                $confirmedCount = $this->shiftRepository
                    ->countConfirmed($id);

                if ((int) $data['max_personen'] < $confirmedCount) {
                    throw new DomainException(
                        sprintf(
                            'De capaciteit kan niet lager zijn dan het huidige aantal van %d bevestigde vrijwilligers.',
                            $confirmedCount
                        )
                    );
                }

                $activeRegistrations = array_values(
                    array_filter(
                        $this->registrationRepository->findByShift($id),
                        static fn (ShiftRegistration $registration): bool =>
                            $registration->isActief()
                    )
                );

                usort(
                    $activeRegistrations,
                    static fn (ShiftRegistration $left, ShiftRegistration $right): int =>
                        $left->lidId <=> $right->lidId
                );

                foreach ($activeRegistrations as $registration) {
                    $this->assertNoScheduleOverlap(
                        $registration->lidId,
                        (string) $data['start_op'],
                        (string) $data['eind_op'],
                        $id
                    );
                }

                $this->shiftRepository->update(
                    $id,
                    $data
                );

                $this->auditLog->updated(
                    entity: 'shift',
                    id: $id,
                    userId: Auth::id(),
                    oldValues: $shift->toAuditArray(),
                    newValues: $data
                );
            }
        );
    }

    public function cancelShift(
        int $id,
        ?string $reason = null,
        ?int $cancelledBy = null
    ): int {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shift.'
            );
        }

        $reason = $this->normalizeReason(
            $reason,
            'Shift geannuleerd door een administrator.'
        );

        $this->registrationValidator
            ->validateCancellationReason($reason);

        return $this->database->transaction(
            function () use ($id, $reason, $cancelledBy): int {
                $shift = $this->shiftRepository->lockForUpdate($id);

                if ($shift === null) {
                    throw new InvalidArgumentException(
                        'Shift niet gevonden.'
                    );
                }

                if ($shift->isGeannuleerd()) {
                    throw new DomainException(
                        'Deze shift is al geannuleerd.'
                    );
                }

                $userId = $cancelledBy
                    ?? $this->requireAuthenticatedUserId();

                $registrations = $this->registrationRepository
                    ->findByShift($id);

                $activeRegistrations = array_values(
                    array_filter(
                        $registrations,
                        static fn(
                            ShiftRegistration $registration
                        ): bool => $registration->isActief()
                    )
                );

                $this->shiftRepository->setStatus(
                    $id,
                    Shift::STATUS_GEANNULEERD
                );

                $this->registrationRepository->cancelActiveByShift(
                    shiftId: $id,
                    cancelledBy: $userId,
                    reason: $reason
                );

                $newShiftValues = $shift->toAuditArray();
                $newShiftValues['status'] = Shift::STATUS_GEANNULEERD;

                $this->auditLog->updated(
                    entity: 'shift',
                    id: $id,
                    userId: $userId,
                    oldValues: $shift->toAuditArray(),
                    newValues: $newShiftValues
                );

                foreach ($activeRegistrations as $registration) {
                    $updated = $this->registrationRepository->find(
                        $registration->inschrijvingId
                    );

                    if ($updated === null) {
                        continue;
                    }

                    $this->auditLog->updated(
                        entity: 'shift_registration',
                        id: $registration->inschrijvingId,
                        userId: $userId,
                        oldValues: $registration->toAuditArray(),
                        newValues: $updated->toAuditArray()
                    );
                }

                return count($activeRegistrations);
            }
        );
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shift.'
            );
        }

        $this->database->transaction(
            function () use ($id): void {
                $shift = $this->shiftRepository->lockForUpdate($id);

                if ($shift === null) {
                    throw new InvalidArgumentException(
                        'Shift niet gevonden.'
                    );
                }

                if ($this->shiftRepository->countRegistrations($id) > 0) {
                    throw new DomainException(
                        'Een shift met inschrijvingen kan niet worden verwijderd. Annuleer de shift in plaats daarvan.'
                    );
                }

                $this->shiftRepository->delete($id);

                $this->auditLog->deleted(
                    entity: 'shift',
                    id: $id,
                    userId: Auth::id(),
                    oldValues: $shift->toAuditArray()
                );
            }
        );
    }

    public function approve(int $registrationId): void
    {
        $this->changeDecision(
            registrationId: $registrationId,
            targetStatus: ShiftRegistration::STATUS_BEVESTIGD
        );
    }

    public function reserve(int $registrationId): void
    {
        $this->changeDecision(
            registrationId: $registrationId,
            targetStatus: ShiftRegistration::STATUS_RESERVE
        );
    }

    public function reject(int $registrationId): void
    {
        $this->changeDecision(
            registrationId: $registrationId,
            targetStatus: ShiftRegistration::STATUS_GEWEIGERD
        );
    }

    public function cancelByAdmin(
        int $registrationId,
        ?string $reason = null
    ): void {
        $reason = $this->normalizeReason(
            $reason,
            'Inschrijving geannuleerd door een administrator.'
        );

        $this->registrationValidator
            ->validateCancellationReason($reason);

        $this->cancelRegistration(
            registrationId: $registrationId,
            reason: $reason
        );
    }

    public function cancelActiveAssignmentsForEventMember(
        int $eventId,
        int $memberId
    ): int {
        if ($eventId <= 0 || $memberId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige evenement- of ledenreferentie.'
            );
        }

        return $this->database->transaction(
            function () use ($eventId, $memberId): int {
                $registrations = $this->registrationRepository
                    ->findActiveByEventAndMember($eventId, $memberId);

                foreach ($registrations as $registration) {
                    $this->cancelRegistration(
                        $registration->inschrijvingId,
                        'Evenementdeelname geannuleerd na verificatie van de annulatieaanvraag.'
                    );
                }

                return count($registrations);
            }
        );
    }

    public function setPresence(
        int $registrationId,
        bool $present
    ): void {
        if ($registrationId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shiftinschrijving.'
            );
        }

        $this->database->transaction(
            function () use ($registrationId, $present): void {
                $registration = $this->registrationRepository
                    ->findForUpdate($registrationId);

                if ($registration === null) {
                    throw new InvalidArgumentException(
                        'Shiftinschrijving niet gevonden.'
                    );
                }

                if (!$registration->isBevestigd()) {
                    throw new DomainException(
                        'Aanwezigheid kan alleen voor bevestigde vrijwilligers worden geregistreerd.'
                    );
                }

                if ($registration->aanwezig === $present) {
                    return;
                }

                $this->registrationRepository->setPresence(
                    $registrationId,
                    $present
                );

                $updated = $this->registrationRepository->find(
                    $registrationId
                );

                if ($updated === null) {
                    throw new RuntimeException(
                        'De gewijzigde shiftinschrijving kon niet worden geladen.'
                    );
                }

                $this->auditLog->updated(
                    entity: 'shift_registration',
                    id: $registrationId,
                    userId: Auth::id(),
                    oldValues: $registration->toAuditArray(),
                    newValues: $updated->toAuditArray()
                );
            }
        );
    }

    private function changeDecision(
        int $registrationId,
        string $targetStatus
    ): void {
        if ($registrationId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shiftinschrijving.'
            );
        }

        if (
            !in_array(
                $targetStatus,
                [
                    ShiftRegistration::STATUS_BEVESTIGD,
                    ShiftRegistration::STATUS_RESERVE,
                    ShiftRegistration::STATUS_GEWEIGERD,
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Ongeldige beslissing.'
            );
        }

        $registrationSnapshot = $this->registrationRepository->find(
            $registrationId
        );

        if ($registrationSnapshot === null) {
            throw new InvalidArgumentException(
                'Shiftinschrijving niet gevonden.'
            );
        }

        $this->database->transaction(
            function () use (
                $registrationId,
                $targetStatus,
                $registrationSnapshot
            ): void {
                $shift = $this->shiftRepository->lockForUpdate(
                    $registrationSnapshot->shiftId
                );

                if ($shift === null) {
                    throw new InvalidArgumentException(
                        'Shift niet gevonden.'
                    );
                }

                $registration = $this->registrationRepository
                    ->findForUpdate($registrationId);

                if ($registration === null) {
                    throw new InvalidArgumentException(
                        'Shiftinschrijving niet gevonden.'
                    );
                }

                if ($registration->shiftId !== $shift->shiftId) {
                    throw new RuntimeException(
                        'De shiftinschrijving werd gelijktijdig gewijzigd.'
                    );
                }

                if ($registration->status === $targetStatus) {
                    return;
                }

                if (
                    !in_array(
                        $registration->status,
                        [
                            ShiftRegistration::STATUS_WACHTEND,
                            ShiftRegistration::STATUS_RESERVE,
                        ],
                        true
                    )
                ) {
                    throw new DomainException(
                        'Deze shiftinschrijving kan niet meer op deze manier worden beoordeeld.'
                    );
                }

                if (!$shift->isActief()) {
                    throw new DomainException(
                        'Een toewijzing op een geannuleerde shift kan niet meer worden beoordeeld.'
                    );
                }

                if ($targetStatus !== ShiftRegistration::STATUS_GEWEIGERD) {
                    $event = $this->eventRepository->lockForUpdate(
                        $shift->eventId
                    );
                    $eventRegistration = $this->eventRegistrationRepository
                        ->findByEventAndMember(
                            $shift->eventId,
                            $registration->lidId
                        );

                    if ($event === null || $eventRegistration === null) {
                        throw new DomainException(
                            'Dit lid heeft geen geldige evenementinschrijving.'
                        );
                    }

                    $eventRegistration = $this->eventRegistrationRepository
                        ->findForUpdate(
                            $eventRegistration->inschrijvingId
                        );
                    $shiftDate = (new DateTimeImmutable($shift->startOp))
                        ->format('Y-m-d');

                    if (
                        $eventRegistration === null
                        || !$eventRegistration->isBevestigd()
                        || $eventRegistration->hasPendingCancellation()
                        || !$eventRegistration->coversDate($shiftDate)
                    ) {
                        throw new DomainException(
                            'Alleen bevestigde evenementdeelnemers die voor deze dag beschikbaar zijn, kunnen worden toegewezen.'
                        );
                    }

                    $this->assertNoScheduleOverlap(
                        $registration->lidId,
                        $shift->startOp,
                        $shift->eindOp,
                        $shift->shiftId
                    );
                }

                if (
                    $targetStatus === ShiftRegistration::STATUS_BEVESTIGD
                    && $this->registrationRepository->countByStatus(
                        $shift->shiftId,
                        ShiftRegistration::STATUS_BEVESTIGD
                    ) >= $shift->maxPersonen
                ) {
                    throw new DomainException(
                        'Deze shift is volzet. Plaats het lid op de reservelijst.'
                    );
                }

                $userId = $this->requireAuthenticatedUserId();

                $this->registrationRepository->setDecision(
                    id: $registrationId,
                    status: $targetStatus,
                    approvedBy: $userId
                );

                $updated = $this->registrationRepository->find(
                    $registrationId
                );

                if ($updated === null) {
                    throw new RuntimeException(
                        'De gewijzigde shiftinschrijving kon niet worden geladen.'
                    );
                }

                $this->auditLog->updated(
                    entity: 'shift_registration',
                    id: $registrationId,
                    userId: $userId,
                    oldValues: $registration->toAuditArray(),
                    newValues: $updated->toAuditArray()
                );
            }
        );
    }

    private function memberHasEventDay(Shift $shift, int $memberId): bool
    {
        $registration = $this->eventRegistrationRepository
            ->findByEventAndMember($shift->eventId, $memberId);

        if (
            $registration === null
            || !$registration->isActief()
            || $registration->hasPendingCancellation()
        ) {
            return false;
        }

        $shiftDate = (new DateTimeImmutable($shift->startOp))
            ->format('Y-m-d');

        return $registration->coversDate($shiftDate);
    }

    private function assertNoScheduleOverlap(
        int $memberId,
        string $startAt,
        string $endAt,
        int $excludeShiftId
    ): void {
        $this->registrationRepository->lockMemberSchedule($memberId);
        $overlap = $this->registrationRepository->findOverlapForUpdate(
            $memberId,
            $startAt,
            $endAt,
            $excludeShiftId
        );

        if ($overlap === null) {
            return;
        }

        $start = (new DateTimeImmutable($overlap['start_op']))
            ->format('d/m/Y H:i');
        $end = (new DateTimeImmutable($overlap['eind_op']))
            ->format('H:i');

        throw new DomainException(
            sprintf(
                'Dit lid is al ingeschreven voor “%s” van %s tot %s. Overlappende shifts zijn niet toegestaan.',
                $overlap['naam'],
                $start,
                $end
            )
        );
    }

    private function cancelRegistration(
        int $registrationId,
        string $reason
    ): void {
        if ($registrationId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shiftinschrijving.'
            );
        }

        $registrationSnapshot = $this->registrationRepository->find(
            $registrationId
        );

        if ($registrationSnapshot === null) {
            throw new InvalidArgumentException(
                'Shiftinschrijving niet gevonden.'
            );
        }

        $this->database->transaction(
            function () use (
                $registrationId,
                $registrationSnapshot,
                $reason
            ): void {
                $shift = $this->shiftRepository->lockForUpdate(
                    $registrationSnapshot->shiftId
                );

                if ($shift === null) {
                    throw new InvalidArgumentException(
                        'Shift niet gevonden.'
                    );
                }

                $registration = $this->registrationRepository
                    ->findForUpdate($registrationId);

                if ($registration === null) {
                    throw new InvalidArgumentException(
                        'Shiftinschrijving niet gevonden.'
                    );
                }

                if (!$registration->isActief()) {
                    throw new DomainException(
                        'Deze shiftinschrijving is niet meer actief.'
                    );
                }

                $userId = $this->requireAuthenticatedUserId();

                $this->registrationRepository->cancel(
                    id: $registrationId,
                    cancelledBy: $userId,
                    reason: $reason
                );

                $updated = $this->registrationRepository->find(
                    $registrationId
                );

                if ($updated === null) {
                    throw new RuntimeException(
                        'De geannuleerde shiftinschrijving kon niet worden geladen.'
                    );
                }

                $this->auditLog->updated(
                    entity: 'shift_registration',
                    id: $registrationId,
                    userId: $userId,
                    oldValues: $registration->toAuditArray(),
                    newValues: $updated->toAuditArray()
                );
            }
        );
    }

    private function requireEvent(int $eventId): Event
    {
        if ($eventId <= 0) {
            throw new InvalidArgumentException(
                'Kies een geldig evenement.'
            );
        }

        return $this->eventRepository->find($eventId)
            ?? throw new InvalidArgumentException(
                'Evenement niet gevonden.'
            );
    }

    private function requireType(int $typeId): ShiftType
    {
        if ($typeId <= 0) {
            throw new InvalidArgumentException(
                'Kies een geldig shifttype.'
            );
        }

        return $this->typeRepository->find($typeId)
            ?? throw new InvalidArgumentException(
                'Shifttype niet gevonden.'
            );
    }

    private function requireAuthenticatedUserId(): int
    {
        $userId = Auth::id();

        if ($userId === null || $userId <= 0) {
            throw new RuntimeException(
                'Er is geen aangemelde gebruiker beschikbaar.'
            );
        }

        return $userId;
    }

    private function normalizeReason(
        ?string $reason,
        string $default
    ): string {
        return $this->normalizeNullableString($reason)
            ?? $default;
    }

    private function normalizeNullableString(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}
