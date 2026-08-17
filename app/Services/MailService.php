<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Application;
use AEFS\Core\Auth;
use AEFS\Core\Database;
use AEFS\Core\Http\UploadedFile;
use App\Mail\MailContent;
use App\Mail\MailTemplateRenderer;
use App\Mail\RecipientPolicy;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Mailing;
use App\Models\MailingRecipient;
use App\Models\User;
use App\Repositories\EventRepository;
use App\Repositories\MailingRepository;
use App\Validators\MailingValidator;
use DomainException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class MailService
{
    public function __construct(
        private readonly Application $application,
        private readonly Database $database,
        private readonly MailingRepository $repository,
        private readonly EventRepository $eventRepository,
        private readonly MailingValidator $validator,
        private readonly MailTemplateRenderer $templates,
        private readonly RecipientPolicy $recipientPolicy,
        private readonly AuditLogService $auditLog
    ) {
    }

    /**
     * @return Mailing[]
     */
    public function latest(): array
    {
        return $this->repository->latest();
    }

    public function find(int $mailingId): ?Mailing
    {
        return $mailingId > 0
            ? $this->repository->find($mailingId)
            : null;
    }

    /**
     * @return MailingRecipient[]
     */
    public function recipients(int $mailingId): array
    {
        return $mailingId > 0
            ? $this->repository->recipients($mailingId)
            : [];
    }

    /**
     * @return array{queued: int, sent: int, failed: int, total: int}
     */
    public function totals(): array
    {
        return $this->repository->totals();
    }

    /**
     * @return array<string, mixed>
     */
    public function audienceOptions(): array
    {
        return $this->repository->audienceOptions();
    }

    /**
     * @return array{active: bool, emails: string[]}
     */
    public function recipientRestriction(): array
    {
        return [
            'active' => $this->recipientPolicy->isRestricted(),
            'emails' => $this->recipientPolicy->allowedEmails(),
        ];
    }

    public function queueEventPublished(Event $event): int
    {
        $members = $this->repository->eligibleAllMembers();

        return $this->createPersonalizedMailing(
            type: 'event_gepubliceerd',
            audienceType: 'alle_leden',
            audience: [
                'event_id' => $event->eventId,
            ],
            eventId: $event->eventId,
            createdBy: Auth::id(),
            members: $members,
            content: fn(array $member): MailContent => $this->templates
                ->eventPublished(
                    $event,
                    (string) ($member['voornaam'] ?? '')
                )
        );
    }

    public function queueEventDecision(
        Event $event,
        EventRegistration $registration,
        string $status
    ): ?int {
        if (!in_array($status, ['bevestigd', 'reserve'], true)) {
            return null;
        }

        $member = $this->repository->eligibleMember(
            $registration->lidId
        );

        if ($member === null) {
            return null;
        }

        if (!$this->recipientPolicy->allows((string) $member['email'])) {
            return null;
        }

        return $this->createPersonalizedMailing(
            type: $status === 'bevestigd'
                ? 'event_bevestigd'
                : 'event_reserve',
            audienceType: 'automatisch',
            audience: [
                'event_id' => $event->eventId,
                'inschrijving_id' => $registration->inschrijvingId,
                'lid_id' => $registration->lidId,
            ],
            eventId: $event->eventId,
            createdBy: Auth::id(),
            members: [$member],
            content: fn(array $recipient): MailContent => $this->templates
                ->eventDecision(
                    $event,
                    (string) ($recipient['voornaam'] ?? ''),
                    $status
                )
        );
    }

    public function queueEventCancellation(Event $event): int
    {
        $members = $this->repository
            ->eligibleEventCancellationRecipients($event->eventId);

        if ($this->recipientPolicy->isRestricted()) {
            $allowedMembers = $this->recipientPolicy->filter($members);

            if (count($allowedMembers) !== count($members)) {
                throw new DomainException(
                    'Dit evenement kan in de lokale mailtestmodus niet worden geannuleerd omdat niet alle betrokken leden op de testontvangerslijst staan.'
                );
            }
        }

        return $this->createPersonalizedMailing(
            type: 'event_geannuleerd',
            audienceType: 'betrokken_eventleden',
            audience: [
                'event_id' => $event->eventId,
                'event_inschrijvingen' => true,
                'bevestigde_shiftvrijwilligers' => true,
            ],
            eventId: $event->eventId,
            createdBy: Auth::id(),
            members: $members,
            content: fn(array $member): MailContent => $this->templates
                ->eventCancelled(
                    $event,
                    (string) ($member['voornaam'] ?? ''),
                    (bool) ($member['heeft_bevestigde_shift'] ?? false)
                )
        );
    }

    public function queueShiftPlanning(
        int $eventId,
        int $createdBy
    ): int {
        $event = $this->eventRepository->find($eventId);

        if ($event === null) {
            throw new InvalidArgumentException(
                'Evenement niet gevonden.'
            );
        }

        if ($event->isCancelled()) {
            throw new DomainException(
                'Voor een geannuleerd evenement kan geen shiftplanning worden verstuurd.'
            );
        }

        $rows = $this->recipientPolicy->filter(
            $this->repository->confirmedShiftPlanning($eventId)
        );

        if ($rows === []) {
            throw new DomainException(
                'Er zijn geen bevestigde shifttoewijzingen met een geldig e-mailadres.'
            );
        }

        $members = [];
        $shiftsByMember = [];

        foreach ($rows as $row) {
            $memberId = (int) $row['lid_id'];
            $members[$memberId] ??= [
                'lid_id' => $memberId,
                'voornaam' => (string) $row['voornaam'],
                'achternaam' => (string) $row['achternaam'],
                'naam' => (string) $row['naam'],
                'email' => strtolower(trim((string) $row['email'])),
            ];
            $shiftsByMember[$memberId][] = $row;
        }

        return $this->createPersonalizedMailing(
            type: 'shift_planning',
            audienceType: 'evenement_shifts',
            audience: [
                'event_id' => $eventId,
            ],
            eventId: $eventId,
            createdBy: $createdBy,
            members: array_values($members),
            content: fn(array $member): MailContent => $this->templates
                ->shiftPlanning(
                    $event,
                    (string) ($member['voornaam'] ?? ''),
                    $shiftsByMember[(int) $member['lid_id']] ?? []
                )
        );
    }

    public function queuePasswordReset(
        User $user,
        string $token
    ): ?int {
        if (
            !filter_var($user->email, FILTER_VALIDATE_EMAIL)
            || !$this->recipientPolicy->allows($user->email)
        ) {
            return null;
        }

        return $this->createPersonalizedMailing(
            type: 'wachtwoord_reset',
            audienceType: 'automatisch',
            audience: [
                'lid_id' => $user->lidId,
                'beveiligde_herstelmail' => true,
            ],
            eventId: null,
            createdBy: null,
            members: [[
                'lid_id' => $user->lidId,
                'voornaam' => $user->voornaam,
                'achternaam' => $user->achternaam,
                'naam' => $user->fullName(),
                'email' => $user->email,
            ]],
            content: fn(array $member): MailContent => $this->templates
                ->passwordReset(
                    (string) ($member['voornaam'] ?? ''),
                    $token
                ),
            summaryContent: $this->templates->passwordResetSummary()
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function queueManual(
        array $data,
        ?UploadedFile $attachment,
        int $createdBy
    ): int {
        $this->validator->validate($data, $attachment);
        $members = $this->recipientPolicy->filter(
            $this->membersForAudience($data)
        );

        if ($members === []) {
            throw new DomainException(
                'De gekozen doelgroep bevat geen actieve leden met een geldig e-mailadres.'
            );
        }

        $storedAttachment = $this->storeAttachment($attachment);

        try {
            return $this->database->transaction(
                function () use (
                    $data,
                    $members,
                    $createdBy,
                    $storedAttachment
                ): int {
                    $mailingId = $this->createPersonalizedMailing(
                        type: 'manueel',
                        audienceType: (string) $data['doelgroep_type'],
                        audience: $this->audienceSnapshot($data),
                        eventId: null,
                        createdBy: $createdBy,
                        members: $members,
                        content: fn(array $member): MailContent => $this->templates
                            ->manual(
                                (string) $data['onderwerp'],
                                (string) $data['inhoud'],
                                (string) ($member['voornaam'] ?? '')
                            )
                    );

                    if ($storedAttachment !== null) {
                        $this->repository->addAttachment(
                            $mailingId,
                            $storedAttachment
                        );
                    }

                    $this->auditLog->created(
                        entity: 'mailing',
                        id: $mailingId,
                        userId: $createdBy,
                        values: [
                            'type' => 'manueel',
                            'doelgroep' => $this->audienceSnapshot($data),
                            'onderwerp' => $data['onderwerp'],
                            'ontvangers' => count($members),
                            'bijlage' => $storedAttachment['name'] ?? null,
                        ]
                    );

                    return $mailingId;
                }
            );
        } catch (Throwable $throwable) {
            if ($storedAttachment !== null) {
                $absolutePath = $this->application->basePath(
                    $storedAttachment['path']
                );

                if (is_file($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            throw $throwable;
        }
    }

    public function retryFailed(
        int $mailingId,
        int $userId
    ): int {
        if ($this->repository->find($mailingId) === null) {
            throw new InvalidArgumentException(
                'Mailing niet gevonden.'
            );
        }

        $count = $this->repository->retryFailed($mailingId);

        if ($count > 0) {
            $this->auditLog->updated(
                entity: 'mailing',
                id: $mailingId,
                userId: $userId,
                oldValues: ['mislukte_ontvangers' => $count],
                newValues: ['opnieuw_ingepland' => $count]
            );
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<string, mixed>>
     */
    private function membersForAudience(array $data): array
    {
        return match ($data['doelgroep_type'] ?? '') {
            'alle_leden' => $this->repository->eligibleAllMembers(),
            'groep' => $this->repository->eligibleMembersByGroups(
                $data['groep_ids'] ?? []
            ),
            'evenement' => $this->repository->eligibleMembersByEvents(
                $data['event_ids'] ?? []
            ),
            'shifts' => $this->repository->eligibleMembersByShifts(
                $data['shift_ids'] ?? []
            ),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function audienceSnapshot(array $data): array
    {
        return match ($data['doelgroep_type'] ?? '') {
            'groep' => [
                'groep_ids' => $data['groep_ids'] ?? [],
            ],
            'evenement' => [
                'event_ids' => $data['event_ids'] ?? [],
            ],
            'shifts' => [
                'shift_ids' => $data['shift_ids'] ?? [],
            ],
            default => [
                'alle_actieve_leden' => true,
            ],
        };
    }

    /**
     * @param array<string, mixed> $audience
     * @param array<int, array<string, mixed>> $members
     * @param callable(array<string, mixed>): MailContent $content
     */
    private function createPersonalizedMailing(
        string $type,
        string $audienceType,
        array $audience,
        ?int $eventId,
        ?int $createdBy,
        array $members,
        callable $content,
        ?MailContent $summaryContent = null
    ): int {
        $members = $this->recipientPolicy->filter($members);
        $recipients = [];
        $baseContent = null;

        foreach ($members as $member) {
            $email = strtolower(trim((string) ($member['email'] ?? '')));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $mailContent = $content($member);
            $baseContent ??= $mailContent;
            if (isset($recipients[$email])) {
                continue;
            }

            $recipients[$email] = [
                'lid_id' => (int) ($member['lid_id'] ?? 0) ?: null,
                'email' => $email,
                'naam' => trim((string) ($member['naam'] ?? '')),
                'onderwerp' => $mailContent->subject,
                'inhoud_html' => $mailContent->html,
                'inhoud_tekst' => $mailContent->text,
            ];
        }

        if ($baseContent === null) {
            $baseContent = $content([]);
        }

        $summaryContent ??= $baseContent;

        $audienceJson = json_encode(
            $audience,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        return $this->repository->create(
            [
                'type' => $type,
                'doelgroep_type' => $audienceType,
                'doelgroep_json' => $audienceJson,
                'event_id' => $eventId,
                'aangemaakt_door' => $createdBy,
                'onderwerp' => $summaryContent->subject,
                'inhoud_html' => $summaryContent->html,
                'inhoud_tekst' => $summaryContent->text,
            ],
            array_values($recipients)
        );
    }

    /**
     * @return array{
     *     name: string,
     *     path: string,
     *     mime: string,
     *     size: int,
     *     sha256: string
     * }|null
     */
    private function storeAttachment(
        ?UploadedFile $attachment
    ): ?array {
        if (
            $attachment === null
            || $attachment->error() === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        $this->validator->validateAttachment($attachment);
        $extension = $attachment->clientExtension();
        $filename = bin2hex(random_bytes(20)) . '.' . $extension;
        $directory = $this->application->storagePath(
            'mail-attachments'
        );
        $absolutePath = $attachment->move($directory, $filename);
        $mime = 'application/octet-stream';

        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($absolutePath);

            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        $hash = hash_file('sha256', $absolutePath);

        if (!is_string($hash)) {
            unlink($absolutePath);

            throw new RuntimeException(
                'De integriteitscontrole van de bijlage is mislukt.'
            );
        }

        return [
            'name' => basename($attachment->originalName()),
            'path' => 'storage/mail-attachments/' . $filename,
            'mime' => $mime,
            'size' => $attachment->size(),
            'sha256' => $hash,
        ];
    }
}
