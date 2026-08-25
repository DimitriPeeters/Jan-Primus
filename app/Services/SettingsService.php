<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Config;
use AEFS\Core\Database;
use App\Mail\RecipientPolicy;
use App\Models\ShiftType;
use App\Repositories\MailingRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\ShiftTypeRepository;
use App\Validators\SettingsValidator;
use App\Validators\ShiftTypeValidator;
use DomainException;
use InvalidArgumentException;

final class SettingsService
{
    /**
     * @var array<string, string>
     */
    private const DEFAULTS = [
        'application_name' => 'Ledenbeheer',
        'organization_name' => 'vzw Jan Primus',
        'mail_from_name' => 'vzw Jan Primus',
        'mail_reply_to_name' => 'vzw Jan Primus',
        'mail_reply_to_address' => 'info@jan-primus.be',
        'default_shift_compensation' => '30.00',
        'group_supplement' => '10.00',
        'default_event_uses_groups' => '0',
    ];

    /**
     * @var array<string, array{id: int, value: string}>|null
     */
    private ?array $stored = null;

    public function __construct(
        private readonly Database $database,
        private readonly SettingsRepository $settings,
        private readonly ShiftTypeRepository $shiftTypes,
        private readonly SettingsValidator $settingsValidator,
        private readonly ShiftTypeValidator $shiftTypeValidator,
        private readonly AuditLogService $auditLog,
        private readonly Config $config,
        private readonly MailingRepository $mailings,
        private readonly RecipientPolicy $recipientPolicy
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $values = self::DEFAULTS;

        foreach ($this->stored() as $key => $setting) {
            if (array_key_exists($key, $values)) {
                $values[$key] = $setting['value'];
            }
        }

        return $values;
    }

    /**
     * @param array<string, string> $data
     */
    public function update(array $data): void
    {
        $this->settingsValidator->validate($data);
        $current = $this->all();
        $userId = Auth::id();

        $this->database->transaction(
            function () use ($data, $current, $userId): void {
                foreach (array_keys(self::DEFAULTS) as $key) {
                    $newValue = (string) $data[$key];
                    $oldValue = (string) $current[$key];

                    if ($newValue === $oldValue) {
                        continue;
                    }

                    $existing = $this->stored()[$key] ?? null;
                    $settingId = $this->settings->upsert(
                        $key,
                        $newValue,
                        $userId
                    );

                    if ($existing === null) {
                        $this->auditLog->created(
                            entity: 'setting',
                            id: $settingId,
                            userId: $userId,
                            values: [
                                'key' => $key,
                                'value' => $newValue,
                            ]
                        );
                    } else {
                        $this->auditLog->updated(
                            entity: 'setting',
                            id: $settingId,
                            userId: $userId,
                            oldValues: [
                                'key' => $key,
                                'value' => $oldValue,
                            ],
                            newValues: [
                                'key' => $key,
                                'value' => $newValue,
                            ]
                        );
                    }
                }
            }
        );

        $this->stored = null;
    }

    public function applicationName(): string
    {
        return $this->value('application_name');
    }

    public function organizationName(): string
    {
        return $this->value('organization_name');
    }

    public function mailFromName(): string
    {
        return $this->value('mail_from_name');
    }

    public function mailReplyToName(): string
    {
        return $this->value('mail_reply_to_name');
    }

    public function mailReplyToAddress(): string
    {
        return $this->value('mail_reply_to_address');
    }

    public function defaultShiftCompensation(): string
    {
        return $this->value('default_shift_compensation');
    }

    public function groupSupplement(): string
    {
        return $this->value('group_supplement');
    }

    public function defaultEventUsesGroups(): bool
    {
        return $this->value('default_event_uses_groups') === '1';
    }

    /**
     * @return array<int, array{type: ShiftType, shift_count: int}>
     */
    public function shiftTypeOverview(): array
    {
        return $this->shiftTypes->allWithShiftCounts();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createShiftType(array $data): int
    {
        $this->shiftTypeValidator->validate($data);

        if ($this->shiftTypes->existsByName((string) $data['naam'])) {
            throw new InvalidArgumentException(
                'Er bestaat al een shiftfunctie met deze naam.'
            );
        }

        return $this->database->transaction(
            function () use ($data): int {
                $id = $this->shiftTypes->create($data);
                $type = $this->shiftTypes->find($id);

                $this->auditLog->created(
                    entity: 'shift_type',
                    id: $id,
                    userId: Auth::id(),
                    values: $type?->toAuditArray() ?? $data
                );

                return $id;
            }
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateShiftType(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ongeldige shiftfunctie.'
            );
        }

        $this->shiftTypeValidator->validate($data);
        $type = $this->shiftTypes->find($id);

        if ($type === null) {
            throw new InvalidArgumentException(
                'Shiftfunctie niet gevonden.'
            );
        }

        if (
            $type->isDefault()
            && strcasecmp($type->naam, (string) $data['naam']) !== 0
        ) {
            throw new DomainException(
                'De standaardfunctie Steward kan niet worden hernoemd.'
            );
        }

        if ($type->isDefault() && !((bool) $data['actief'])) {
            throw new DomainException(
                'De standaardfunctie Steward moet actief blijven.'
            );
        }

        if (
            $this->shiftTypes->existsByName(
                (string) $data['naam'],
                $id
            )
        ) {
            throw new InvalidArgumentException(
                'Er bestaat al een shiftfunctie met deze naam.'
            );
        }

        $this->database->transaction(
            function () use ($id, $data, $type): void {
                $this->shiftTypes->update($id, $data);
                $updated = $this->shiftTypes->find($id);

                $this->auditLog->updated(
                    entity: 'shift_type',
                    id: $id,
                    userId: Auth::id(),
                    oldValues: $type->toAuditArray(),
                    newValues: $updated?->toAuditArray() ?? $data
                );
            }
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function operationalStatus(): array
    {
        $mailEnabled = (bool) $this->config->get(
            'mail.enabled',
            false
        );
        $host = trim((string) $this->config->get('mail.host', ''));
        $fromAddress = trim(
            (string) $this->config->get('mail.from_address', '')
        );
        $applicationUrl = trim(
            (string) $this->config->get('mail.application_url', '')
        );
        $mailConfigured = $mailEnabled
            && $host !== ''
            && trim((string) $this->config->get('mail.username', '')) !== ''
            && trim((string) $this->config->get('mail.password', '')) !== ''
            && filter_var(
                $fromAddress,
                FILTER_VALIDATE_EMAIL
            ) !== false;
        $schedulerEnabled = (bool) $this->config->get(
            'mail_worker.enabled',
            false
        );
        $schedulerToken = trim(
            (string) $this->config->get('mail_worker.token', '')
        );
        $schedulerConfigured = $schedulerEnabled
            && preg_match('/^[a-f0-9]{64}$/i', $schedulerToken) === 1;

        return [
            'mail' => [
                'enabled' => $mailEnabled,
                'configured' => $mailConfigured,
                'host' => $host,
                'from_address' => $fromAddress,
                'application_url' => $applicationUrl,
                'batch_size' => max(
                    1,
                    (int) $this->config->get('mail.batch_size', 25)
                ),
                'max_attempts' => max(
                    1,
                    (int) $this->config->get('mail.max_attempts', 5)
                ),
                'restriction' => [
                    'active' => $this->recipientPolicy->isRestricted(),
                    'emails' => $this->recipientPolicy->allowedEmails(),
                ],
                'totals' => $this->mailings->totals(),
                'scheduler' => [
                    'enabled' => $schedulerEnabled,
                    'configured' => $schedulerConfigured,
                    'https_required' => true,
                ],
            ],
            'system' => [
                'environment' => (string) $this->config->get(
                    'app.environment',
                    'production'
                ),
                'timezone' => (string) $this->config->get(
                    'app.timezone',
                    'Europe/Brussels'
                ),
                'app_key_configured' => trim(
                    (string) $this->config->get('app.app_key', '')
                ) !== '',
                'php_version' => PHP_VERSION,
            ],
        ];
    }

    private function value(string $key): string
    {
        return $this->all()[$key] ?? self::DEFAULTS[$key] ?? '';
    }

    /**
     * @return array<string, array{id: int, value: string}>
     */
    private function stored(): array
    {
        return $this->stored ??= $this->settings->all();
    }

}
