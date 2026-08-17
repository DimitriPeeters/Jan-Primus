<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Database;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Mailing;
use App\Models\Shift;
use App\Models\ShiftType;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\EventRepository;
use App\Validators\EvenementenValidator;
use App\Validators\EventRegistrationValidator;
use DomainException;
use InvalidArgumentException;
use RuntimeException;

final class EventService
{
    public function __construct(
        private readonly Database $database,
        private readonly EventRepository $repository,
        private readonly EventRegistrationRepository $registrationRepository,
        private readonly EvenementenValidator $validator,
        private readonly EventRegistrationValidator $registrationValidator,
        private readonly ShiftService $shiftService,
        private readonly MailService $mailService,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @return Event[]
     */
    public function allForAdministration(): array
    {
        return $this->repository->allForAdministration();
    }

    /**
     * @return Event[]
     */
    public function visibleToMembers(): array
    {
        return $this->repository->visibleToMembers();
    }

    /**
     * @return Event[]
     */
    public function searchForAdministration(string $zoekterm): array
    {
        $zoekterm = trim($zoekterm);

        return $zoekterm === ''
            ? $this->allForAdministration()
            : $this->repository->searchForAdministration($zoekterm);
    }

    /**
     * @return Event[]
     */
    public function searchVisibleToMembers(string $zoekterm): array
    {
        $zoekterm = trim($zoekterm);

        return $zoekterm === ''
            ? $this->visibleToMembers()
            : $this->repository->searchVisibleToMembers($zoekterm);
    }

    public function find(int $id): ?Event
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->find($id);
    }

    public function findVisibleToMembers(int $id): ?Event
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repository->findVisibleToMembers($id);
    }

    /**
     * @return ShiftType[]
     */
    public function activeShiftTypes(): array
    {
        return $this->shiftService->activeTypes();
    }

    /**
     * @return Shift[]
     */
    public function shiftsForEvent(int $eventId): array
    {
        return $this->shiftService->findByEvent($eventId);
    }

    /**
     * @return EventRegistration[]
     */
    public function registrationsForEvent(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }

        return $this->registrationRepository->findByEvent($eventId);
    }

    public function registrationForMember(
        int $eventId,
        int $memberId
    ): ?EventRegistration {
        if ($eventId <= 0 || $memberId <= 0) {
            return null;
        }

        return $this->registrationRepository->findByEventAndMember(
            $eventId,
            $memberId
        );
    }

    public function findRegistration(int $id): ?EventRegistration
    {
        if ($id <= 0) {
            return null;
        }

        return $this->registrationRepository->find($id);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $shifts
     */
    public function create(array $data, array $shifts = []): int
    {
        $this->validator->validate($data);

        return $this->database->transaction(
            function () use ($data, $shifts): int {
                $id = $this->repository->create($data);

                $this->auditLog->created(
                    entity: 'event',
                    id: $id,
                    userId: Auth::id(),
                    values: $data
                );

                $this->createShiftsForEvent($id, $shifts);

                $event = $this->repository->find($id);

                if ($event !== null && $event->isPublished()) {
                    $this->mailService->queueEventPublished($event);
                }

                return $id;
            }
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $newShifts
     */
    public function update(
        int $id,
        array $data,
        array $newShifts = []
    ): void {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldig evenement.'
            );
        }

        $this->validator->validate($data);

        $this->database->transaction(
            function () use ($id, $data, $newShifts): void {
                $event = $this->repository->lockForUpdate($id);

                if ($event === null) {
                    throw new InvalidArgumentException(
                        'Evenement niet gevonden.'
                    );
                }

                $targetStatus = (string) ($data['status'] ?? '');
                $startsCancellation = !$event->isCancelled()
                    && $targetStatus === Event::STATUS_CANCELLED;

                if (
                    $event->isCancelled()
                    && $targetStatus !== Event::STATUS_CANCELLED
                ) {
                    throw new DomainException(
                        'Een geannuleerd evenement kan niet opnieuw worden geactiveerd.'
                    );
                }

                if ($startsCancellation && $newShifts !== []) {
                    throw new DomainException(
                        'Tijdens het annuleren van een evenement kunnen geen nieuwe shifts worden toegevoegd.'
                    );
                }

                $this->repository->update($id, $data);

                $this->auditLog->updated(
                    entity: 'event',
                    id: $id,
                    userId: Auth::id(),
                    oldValues: $event->toAuditArray(),
                    newValues: $data
                );

                if (!$startsCancellation) {
                    $this->createShiftsForEvent($id, $newShifts);
                }

                if ($startsCancellation) {
                    $cancelledEvent = $this->repository->find($id);

                    if ($cancelledEvent === null) {
                        throw new RuntimeException(
                            'Het geannuleerde evenement kon niet worden geladen.'
                        );
                    }

                    $mailingId = $this->mailService
                        ->queueEventCancellation($cancelledEvent);

                    $this->completeEventCancellationAfterNotification(
                        $mailingId
                    );

                    return;
                }

                if (
                    !$event->isPublished()
                    && ($data['status'] ?? null) === Event::STATUS_PUBLISHED
                ) {
                    $publishedEvent = $this->repository->find($id);

                    if ($publishedEvent === null) {
                        throw new RuntimeException(
                            'Het gepubliceerde evenement kon niet worden geladen.'
                        );
                    }

                    $this->mailService->queueEventPublished(
                        $publishedEvent
                    );
                }
            }
        );
    }

    public function completeEventCancellationAfterNotification(
        int $mailingId
    ): bool {
        $mailing = $this->mailService->find($mailingId);

        if (
            $mailing === null
            || $mailing->type !== 'event_geannuleerd'
            || $mailing->status !== Mailing::STATUS_SENT
            || $mailing->eventId === null
        ) {
            return false;
        }

        if ($mailing->createdBy === null || $mailing->createdBy <= 0) {
            throw new RuntimeException(
                'De beheerder van de evenementannulatie kon niet worden bepaald.'
            );
        }

        return $this->database->transaction(
            function () use ($mailing): bool {
                $event = $this->repository->lockForUpdate(
                    (int) $mailing->eventId
                );

                if ($event === null || !$event->isCancelled()) {
                    return false;
                }

                $reason = 'Evenement geannuleerd door een administrator.';
                $changed = false;

                foreach (
                    $this->shiftService->findByEvent(
                        $event->eventId,
                        true
                    ) as $shift
                ) {
                    if (!$shift->isActief()) {
                        continue;
                    }

                    $this->shiftService->cancelShift(
                        $shift->shiftId,
                        $reason,
                        $mailing->createdBy
                    );
                    $changed = true;
                }

                foreach (
                    $this->registrationRepository->findByEvent(
                        $event->eventId
                    ) as $registration
                ) {
                    if (!$registration->isActief()) {
                        continue;
                    }

                    $this->registrationRepository
                        ->cancelForEventCancellation(
                            $registration->inschrijvingId,
                            $mailing->createdBy,
                            $reason
                        );

                    $updated = $this->registrationRepository->find(
                        $registration->inschrijvingId
                    );

                    if ($updated !== null) {
                        $this->auditLog->updated(
                            entity: 'event_registration',
                            id: $registration->inschrijvingId,
                            userId: $mailing->createdBy,
                            oldValues: $registration->toAuditArray(),
                            newValues: $updated->toAuditArray()
                        );
                    }

                    $changed = true;
                }

                return $changed;
            }
        );
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldig evenement.'
            );
        }

        $this->database->transaction(
            function () use ($id): void {
                $event = $this->repository->lockForUpdate($id);

                if ($event === null) {
                    throw new InvalidArgumentException(
                        'Evenement niet gevonden.'
                    );
                }

                $related = $this->repository->relatedDataCounts($id);

                if ($related['inschrijvingen'] > 0) {
                    throw new DomainException(
                        'Dit evenement heeft inschrijvingen en kan daarom niet worden verwijderd. Zet de status op geannuleerd.'
                    );
                }

                foreach ($this->shiftService->findByEvent($id) as $shift) {
                    $this->shiftService->delete($shift->shiftId);
                }

                $this->repository->delete($id);

                $this->auditLog->deleted(
                    entity: 'event',
                    id: $id,
                    userId: Auth::id(),
                    oldValues: $event->toAuditArray()
                );
            }
        );
    }

    /**
     * @param string[] $days
     */
    public function registerMember(
        int $eventId,
        int $memberId,
        array $days
    ): int {
        if ($eventId <= 0 || $memberId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige evenementinschrijving.'
            );
        }

        return $this->database->transaction(
            function () use ($eventId, $memberId, $days): int {
                $event = $this->repository->lockForUpdate($eventId);

                if ($event === null) {
                    throw new InvalidArgumentException(
                        'Evenement niet gevonden.'
                    );
                }

                if (!$event->isPublished()) {
                    throw new DomainException(
                        'Dit evenement staat niet open voor inschrijvingen.'
                    );
                }

                if ($event->isPast()) {
                    throw new DomainException(
                        'Dit evenement is afgelopen.'
                    );
                }

                $this->registrationValidator->validateDays($event, $days);

                $existing = $this->registrationRepository
                    ->findByEventAndMember($eventId, $memberId);

                if (
                    $existing !== null
                    && $existing->hasPendingCancellation()
                ) {
                    throw new DomainException(
                        'Je annulatieaanvraag wacht nog op verwerking door een administrator.'
                    );
                }

                if (
                    $existing !== null
                    && !$existing->isWachtend()
                    && !$existing->isUitgeschreven()
                ) {
                    throw new DomainException(
                        'Deze evenementinschrijving is al beoordeeld en kan niet meer door het lid worden gewijzigd.'
                    );
                }

                $registrationId = $this->registrationRepository->submit(
                    $eventId,
                    $memberId
                );

                $this->registrationRepository->replaceDays(
                    $registrationId,
                    $days
                );

                $registration = $this->registrationRepository->find(
                    $registrationId
                );

                if ($registration === null) {
                    throw new RuntimeException(
                        'De evenementinschrijving kon niet worden geladen.'
                    );
                }

                if ($existing === null) {
                    $this->auditLog->created(
                        entity: 'event_registration',
                        id: $registrationId,
                        userId: Auth::id(),
                        values: $registration->toAuditArray()
                    );
                } else {
                    $this->auditLog->updated(
                        entity: 'event_registration',
                        id: $registrationId,
                        userId: Auth::id(),
                        oldValues: $existing->toAuditArray(),
                        newValues: $registration->toAuditArray()
                    );
                }

                return $registrationId;
            }
        );
    }

    public function requestRegistrationCancellation(
        int $eventId,
        int $memberId,
        ?string $reason
    ): bool {
        if ($eventId <= 0 || $memberId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige evenementinschrijving.'
            );
        }

        $this->registrationValidator->validateCancellationReason($reason);

        return $this->database->transaction(
            function () use ($eventId, $memberId, $reason): bool {
                $event = $this->repository->lockForUpdate($eventId);

                if ($event === null) {
                    throw new InvalidArgumentException(
                        'Evenement niet gevonden.'
                    );
                }

                if ($event->isPast()) {
                    throw new DomainException(
                        'Een inschrijving voor een afgelopen evenement kan niet meer worden geannuleerd.'
                    );
                }

                $snapshot = $this->registrationRepository
                    ->findByEventAndMember($eventId, $memberId);

                if ($snapshot === null) {
                    throw new InvalidArgumentException(
                        'Evenementinschrijving niet gevonden.'
                    );
                }

                $registration = $this->registrationRepository
                    ->findForUpdate($snapshot->inschrijvingId);

                if ($registration === null || !$registration->isActief()) {
                    throw new DomainException(
                        'Deze evenementinschrijving is niet meer actief.'
                    );
                }

                if ($registration->hasPendingCancellation()) {
                    return true;
                }

                $requiresVerification = $this->registrationRepository
                    ->countActiveShiftAssignments($eventId, $memberId) > 0;

                if ($requiresVerification) {
                    $this->registrationRepository->requestCancellation(
                        $registration->inschrijvingId,
                        $reason
                    );
                } else {
                    $this->registrationRepository
                        ->cancelWithoutVerification(
                            $registration->inschrijvingId,
                            $reason
                        );
                }

                $updated = $this->registrationRepository->find(
                    $registration->inschrijvingId
                );

                if ($updated === null) {
                    throw new RuntimeException(
                        'De annulering kon niet worden geregistreerd.'
                    );
                }

                $this->auditLog->updated(
                    entity: 'event_registration',
                    id: $registration->inschrijvingId,
                    userId: Auth::id(),
                    oldValues: $registration->toAuditArray(),
                    newValues: $updated->toAuditArray()
                );

                return $requiresVerification;
            }
        );
    }

    public function confirmRegistrationCancellation(
        int $registrationId
    ): int {
        if ($registrationId <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige evenementinschrijving.'
            );
        }

        return $this->database->transaction(
            function () use ($registrationId): int {
                $registration = $this->registrationRepository->find(
                    $registrationId
                );

                if ($registration === null) {
                    throw new InvalidArgumentException(
                        'Evenementinschrijving niet gevonden.'
                    );
                }

                if (!$registration->hasPendingCancellation()) {
                    throw new DomainException(
                        'Voor deze inschrijving staat geen annulatieaanvraag open.'
                    );
                }

                $cancelledAssignments = $this->shiftService
                    ->cancelActiveAssignmentsForEventMember(
                        $registration->eventId,
                        $registration->lidId
                    );

                $current = $this->registrationRepository->findForUpdate(
                    $registrationId
                );

                if (
                    $current === null
                    || !$current->isActief()
                    || !$current->hasPendingCancellation()
                ) {
                    throw new DomainException(
                        'De annulatieaanvraag is niet meer actief.'
                    );
                }

                $this->registrationRepository->confirmCancellation(
                    $registrationId,
                    $this->requireAuthenticatedUserId()
                );

                $updated = $this->registrationRepository->find(
                    $registrationId
                );

                if ($updated === null) {
                    throw new RuntimeException(
                        'De geannuleerde evenementinschrijving kon niet worden geladen.'
                    );
                }

                $this->auditLog->updated(
                    entity: 'event_registration',
                    id: $registrationId,
                    userId: Auth::id(),
                    oldValues: $current->toAuditArray(),
                    newValues: $updated->toAuditArray()
                );

                return $cancelledAssignments;
            }
        );
    }

    public function approveRegistration(int $registrationId): void
    {
        $this->changeRegistrationStatus(
            $registrationId,
            EventRegistration::STATUS_BEVESTIGD
        );
    }

    public function reserveRegistration(int $registrationId): void
    {
        $this->changeRegistrationStatus(
            $registrationId,
            EventRegistration::STATUS_RESERVE
        );
    }

    public function rejectRegistration(int $registrationId): void
    {
        $this->changeRegistrationStatus(
            $registrationId,
            EventRegistration::STATUS_GEWEIGERD
        );
    }

    /**
     * @param array<int, array<string, mixed>> $shifts
     */
    private function createShiftsForEvent(
        int $eventId,
        array $shifts
    ): void {
        foreach ($shifts as $shift) {
            $shift['event_id'] = $eventId;
            $this->shiftService->create($shift);
        }
    }

    private function changeRegistrationStatus(
        int $registrationId,
        string $targetStatus
    ): void {
        if (
            $registrationId <= 0
            || !in_array(
                $targetStatus,
                [
                    EventRegistration::STATUS_BEVESTIGD,
                    EventRegistration::STATUS_RESERVE,
                    EventRegistration::STATUS_GEWEIGERD,
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Ongeldige beslissing voor de evenementinschrijving.'
            );
        }

        $registration = $this->registrationRepository->find(
            $registrationId
        );

        if ($registration === null) {
            throw new InvalidArgumentException(
                'Evenementinschrijving niet gevonden.'
            );
        }

        $this->database->transaction(
            function () use ($registrationId, $targetStatus): void {
                $initial = $this->registrationRepository->find(
                    $registrationId
                );

                if ($initial === null) {
                    throw new InvalidArgumentException(
                        'Evenementinschrijving niet gevonden.'
                    );
                }

                $event = $this->repository->lockForUpdate(
                    $initial->eventId
                );

                $current = $this->registrationRepository->findForUpdate(
                    $registrationId
                );

                if ($event === null || $current === null) {
                    throw new InvalidArgumentException(
                        'Evenementinschrijving niet gevonden.'
                    );
                }

                if ($event->isCancelled()) {
                    throw new DomainException(
                        'Inschrijvingen voor een geannuleerd evenement kunnen niet meer worden beoordeeld.'
                    );
                }

                if (!$current->isActief()) {
                    throw new DomainException(
                        'Deze evenementinschrijving is niet meer actief.'
                    );
                }

                if ($current->hasPendingCancellation()) {
                    throw new DomainException(
                        'Verwerk eerst de openstaande annulatieaanvraag.'
                    );
                }

                if ($current->status === $targetStatus) {
                    return;
                }

                if (
                    $targetStatus === EventRegistration::STATUS_BEVESTIGD
                    && !$current->isBevestigd()
                    && $event->maxDeelnemers !== null
                    && $this->registrationRepository->countConfirmed(
                        $event->eventId
                    ) >= $event->maxDeelnemers
                ) {
                    throw new DomainException(
                        'De maximumcapaciteit van dit evenement is bereikt.'
                    );
                }

                if (
                    $current->isBevestigd()
                    && $targetStatus !== EventRegistration::STATUS_BEVESTIGD
                    && $this->registrationRepository
                        ->countActiveShiftAssignments(
                            $current->eventId,
                            $current->lidId
                        ) > 0
                ) {
                    throw new DomainException(
                        'Annuleer eerst de actieve shifttoewijzingen van dit lid.'
                    );
                }

                $this->registrationRepository->setStatus(
                    $registrationId,
                    $targetStatus
                );

                $updated = $this->registrationRepository->find(
                    $registrationId
                );

                if ($updated === null) {
                    throw new RuntimeException(
                        'De gewijzigde evenementinschrijving kon niet worden geladen.'
                    );
                }

                $this->auditLog->updated(
                    entity: 'event_registration',
                    id: $registrationId,
                    userId: Auth::id(),
                    oldValues: $current->toAuditArray(),
                    newValues: $updated->toAuditArray()
                );

                if (
                    $targetStatus === EventRegistration::STATUS_BEVESTIGD
                    || $targetStatus === EventRegistration::STATUS_RESERVE
                ) {
                    $this->mailService->queueEventDecision(
                        $event,
                        $updated,
                        $targetStatus
                    );
                }
            }
        );
    }

    private function requireAuthenticatedUserId(): int
    {
        $userId = Auth::id();

        if ($userId === null || $userId <= 0) {
            throw new DomainException(
                'Je moet aangemeld zijn om deze actie uit te voeren.'
            );
        }

        return $userId;
    }
}
