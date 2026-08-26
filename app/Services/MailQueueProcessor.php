<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Application;
use AEFS\Core\Config;
use App\Mail\OutgoingMail;
use App\Mail\Transport\MailTransportInterface;
use App\Repositories\MailingRepository;
use RuntimeException;
use Throwable;

final class MailQueueProcessor
{
    public function __construct(
        private readonly Application $application,
        private readonly Config $config,
        private readonly MailingRepository $repository,
        private readonly MailTransportInterface $transport,
        private readonly EventService $eventService
    ) {
    }

    /**
     * @return array{processed: int, sent: int, failed: int}
     */
    public function process(?int $limit = null): array
    {
        $this->assertConfigured();
        $lock = $this->acquireWorkerLock();

        if ($lock === null) {
            return [
                'processed' => 0,
                'sent' => 0,
                'failed' => 0,
            ];
        }

        try {
            return $this->processLocked($limit);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function processLocked(?int $limit = null): array
    {
        $limit ??= (int) $this->config->get('mail.batch_size', 25);
        $limit = max(1, min(250, $limit));
        $perMinute = max(
            1,
            (int) $this->config->get('mail.rate_limit_per_minute', 10)
        );
        $perHour = max(
            $perMinute,
            (int) $this->config->get('mail.rate_limit_per_hour', 200)
        );
        $intervalMicroseconds = max(
            0,
            (int) $this->config->get(
                'mail.send_interval_milliseconds',
                6000
            ) * 1000
        );
        $maxAttempts = max(
            1,
            (int) $this->config->get('mail.max_attempts', 5)
        );
        $result = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        $this->repository->releaseStaleLocks();
        $this->completeEventCancellations();
        $lastAttemptAt = null;

        for ($index = 0; $index < $limit; $index++) {
            if (
                $this->repository->sentCountSinceSeconds(60) >= $perMinute
                || $this->repository->sentCountSinceSeconds(3600) >= $perHour
            ) {
                break;
            }

            $recipient = $this->repository->claimNext($maxAttempts);

            if ($recipient === null) {
                break;
            }

            $recipientId = (int) $recipient['ontvanger_id'];
            $mailingId = (int) $recipient['mailing_id'];
            $result['processed']++;

            if ($lastAttemptAt !== null && $intervalMicroseconds > 0) {
                $elapsedMicroseconds = (int) round(
                    (microtime(true) - $lastAttemptAt) * 1000000
                );
                $remainingMicroseconds = $intervalMicroseconds
                    - $elapsedMicroseconds;

                if ($remainingMicroseconds > 0) {
                    usleep($remainingMicroseconds);
                }
            }

            $lastAttemptAt = microtime(true);

            try {
                $messageId = $this->transport->send(
                    new OutgoingMail(
                        recipientEmail: (string) $recipient['email'],
                        recipientName: (string) $recipient['naam'],
                        subject: (string) $recipient['onderwerp'],
                        html: (string) $recipient['inhoud_html'],
                        text: (string) $recipient['inhoud_tekst'],
                        attachments: $this->resolveAttachments($mailingId)
                    )
                );
                $this->repository->markSent(
                    $recipientId,
                    $messageId
                );
                $result['sent']++;
            } catch (Throwable $throwable) {
                $this->repository->markFailed(
                    $recipientId,
                    $throwable->getMessage(),
                    $maxAttempts
                );
                $result['failed']++;
            }

            $this->repository->refreshMailingStatus(
                $mailingId,
                $maxAttempts
            );
        }

        $this->completeEventCancellations();

        return $result;
    }

    /**
     * @return resource|null
     */
    private function acquireWorkerLock()
    {
        $path = $this->application->storagePath('mail-worker.lock');
        $lock = fopen($path, 'c+');

        if ($lock === false) {
            throw new RuntimeException(
                'Het lockbestand van de mailworker kon niet worden geopend.'
            );
        }

        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            return null;
        }

        return $lock;
    }

    private function completeEventCancellations(): void
    {
        foreach (
            $this->repository
                ->pendingEventCancellationCompletions() as $mailingId
        ) {
            $this->eventService
                ->completeEventCancellationAfterNotification($mailingId);
        }
    }

    /**
     * @return array<int, array{path: string, name: string}>
     */
    private function resolveAttachments(int $mailingId): array
    {
        $storageRoot = realpath(
            $this->application->storagePath('mail-attachments')
        );

        if ($storageRoot === false) {
            $storageRoot = $this->application->storagePath(
                'mail-attachments'
            );
        }

        $resolved = [];

        foreach ($this->repository->attachments($mailingId) as $attachment) {
            $path = realpath(
                $this->application->basePath($attachment['path'])
            );

            if (
                $path === false
                || !is_file($path)
                || !str_starts_with(
                    strtolower($path),
                    strtolower(rtrim($storageRoot, DIRECTORY_SEPARATOR))
                        . strtolower(DIRECTORY_SEPARATOR)
                )
            ) {
                throw new RuntimeException(
                    'Een bijlage van deze mailing ontbreekt of heeft een ongeldig pad.'
                );
            }

            $hash = hash_file('sha256', $path);

            if (
                !is_string($hash)
                || !hash_equals($attachment['sha256'], $hash)
            ) {
                throw new RuntimeException(
                    'Een bijlage van deze mailing is na het uploaden gewijzigd.'
                );
            }

            $resolved[] = [
                'path' => $path,
                'name' => $attachment['name'],
            ];
        }

        return $resolved;
    }

    private function assertConfigured(): void
    {
        if (!(bool) $this->config->get('mail.enabled', false)) {
            throw new RuntimeException(
                'Mailverzending is uitgeschakeld in config/local/mail.php.'
            );
        }

        $required = [
            'mail.host',
            'mail.username',
            'mail.password',
            'mail.from_address',
        ];

        foreach ($required as $key) {
            if (trim((string) $this->config->get($key, '')) === '') {
                throw new RuntimeException(
                    'De lokale SMTP-configuratie is onvolledig.'
                );
            }
        }
    }
}
