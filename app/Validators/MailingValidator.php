<?php

declare(strict_types=1);

namespace App\Validators;

use AEFS\Core\Config;
use AEFS\Core\Http\UploadedFile;
use InvalidArgumentException;

final class MailingValidator
{
    private const AUDIENCE_TYPES = [
        'alle_leden',
        'evenement',
        'shift',
        'shifts',
    ];

    private const ALLOWED_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'csv',
        'jpg',
        'jpeg',
        'png',
        'zip',
    ];

    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validate(
        array $data,
        ?UploadedFile $attachment
    ): void {
        $audienceType = (string) ($data['doelgroep_type'] ?? '');
        $subject = trim((string) ($data['onderwerp'] ?? ''));
        $body = trim((string) ($data['inhoud'] ?? ''));

        if (!in_array($audienceType, self::AUDIENCE_TYPES, true)) {
            throw new InvalidArgumentException(
                'Kies een geldige doelgroep.'
            );
        }

        $requiredSelection = match ($audienceType) {
            'evenement' => [(int) ($data['event_id'] ?? 0)],
            'shift' => [(int) ($data['shift_id'] ?? 0)],
            'shifts' => $data['shift_ids'] ?? [],
            default => [1],
        };

        if (
            !is_array($requiredSelection)
            || array_filter(
                $requiredSelection,
                static fn(mixed $id): bool => (int) $id > 0
            ) === []
        ) {
            throw new InvalidArgumentException(
                'Selecteer een evenement of minstens één shift.'
            );
        }

        if ($subject === '') {
            throw new InvalidArgumentException(
                'Het onderwerp is verplicht.'
            );
        }

        if ($this->length($subject) > 255) {
            throw new InvalidArgumentException(
                'Het onderwerp mag maximaal 255 tekens bevatten.'
            );
        }

        if ($body === '') {
            throw new InvalidArgumentException(
                'De inhoud van de mail is verplicht.'
            );
        }

        if ($this->length($body) > 50000) {
            throw new InvalidArgumentException(
                'De inhoud van de mail mag maximaal 50.000 tekens bevatten.'
            );
        }

        $this->validateAttachment($attachment);
    }

    public function validateAttachment(
        ?UploadedFile $attachment
    ): void {
        if (
            $attachment === null
            || $attachment->error() === UPLOAD_ERR_NO_FILE
        ) {
            return;
        }

        if (!$attachment->isValid()) {
            throw new InvalidArgumentException(
                'De bijlage kon niet veilig worden ontvangen.'
            );
        }

        $maximum = max(
            1,
            (int) $this->config->get(
                'mail.attachment_max_bytes',
                10 * 1024 * 1024
            )
        );

        if ($attachment->size() <= 0 || $attachment->size() > $maximum) {
            throw new InvalidArgumentException(
                'De bijlage mag maximaal 10 MB groot zijn.'
            );
        }

        if (!in_array(
            $attachment->clientExtension(),
            self::ALLOWED_EXTENSIONS,
            true
        )) {
            throw new InvalidArgumentException(
                'Dit bestandstype is niet toegestaan als bijlage.'
            );
        }
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($value)
            : strlen($value);
    }
}
