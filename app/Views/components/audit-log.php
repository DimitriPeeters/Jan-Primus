<?php

use App\Support\BelgianDateTime;

/** @var array<int, array<string, mixed>> $logs */

$logs ??= [];

$formatAuditValue = static function (
    string $field,
    mixed $value
): string {
    if ($value === null) {
        return '—';
    }

    if (!is_scalar($value)) {
        return (string) json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    $display = (string) $value;

    if (
        in_array(
            $field,
            [
                'geboortedatum',
                'startdatum',
                'einddatum',
            ],
            true
        )
    ) {
        return BelgianDateTime::formatDate(
            $display,
            $display
        );
    }

    if (
        in_array(
            $field,
            [
                'start_op',
                'eind_op',
                'planning_verstuurd',
                'goedgekeurd_op',
                'geannuleerd_op',
                'aanwezig_afgevinkt_op',
                'aangemaakt_op',
                'bijgewerkt_op',
                'created_at',
                'updated_at',
            ],
            true
        )
    ) {
        return BelgianDateTime::formatDateTime(
            $display,
            $display
        );
    }

    return $display;
};
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            Auditlog
        </div>
    </div>

    <div class="card-body">
        <?php if ($logs === []): ?>
            <?= $this->component(
                'empty-state',
                [
                    'title' => 'Nog geen historiek',
                    'text' => 'Voor dit record zijn nog geen wijzigingen geregistreerd.',
                    'icon' => 'history',
                ]
            ) ?>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">Datum</th>
                        <th scope="col">Actie</th>
                        <th scope="col">Gebruiker</th>
                        <th scope="col">Wijzigingen</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $oldValues = json_decode(
                            (string) ($log['old_values'] ?? '[]'),
                            true
                        );

                        $newValues = json_decode(
                            (string) ($log['new_values'] ?? '[]'),
                            true
                        );

                        $oldValues = is_array($oldValues)
                            ? $oldValues
                            : [];

                        $newValues = is_array($newValues)
                            ? $newValues
                            : [];

                        $action = (string) ($log['action'] ?? '');
                        $createdAt = (string) ($log['created_at'] ?? '');
                        ?>

                        <tr>
                            <td>
                                <?= $this->escape(
                                    BelgianDateTime::formatDateTime(
                                        $createdAt
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?php if ($action === 'create'): ?>
                                    <span class="badge badge-success">
                                        Aangemaakt
                                    </span>
                                <?php elseif ($action === 'update'): ?>
                                    <span class="badge badge-warning">
                                        Gewijzigd
                                    </span>
                                <?php elseif ($action === 'delete'): ?>
                                    <span class="badge badge-danger">
                                        Verwijderd
                                    </span>
                                <?php else: ?>
                                    <span class="badge">
                                        <?= $this->escape(
                                            $action !== ''
                                                ? $action
                                                : 'Onbekend'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $this->escape(
                                    (string) ($log['user_id'] ?? '—')
                                ) ?>
                            </td>

                            <td>
                                <?php if ($action === 'update'): ?>
                                    <?php
                                    $changes = [];

                                    foreach ($newValues as $field => $newValue) {
                                        $oldValue = $oldValues[$field] ?? null;

                                        if ($oldValue == $newValue) {
                                            continue;
                                        }

                                        $changes[$field] = [
                                            'old' => $oldValue,
                                            'new' => $newValue,
                                        ];
                                    }
                                    ?>

                                    <?php if ($changes === []): ?>
                                        Geen inhoudelijke wijzigingen geregistreerd.
                                    <?php else: ?>
                                        <table class="table table-sm">
                                            <tbody>
                                            <?php foreach ($changes as $field => $change): ?>
                                                <?php
                                                $oldDisplay = $formatAuditValue(
                                                    (string) $field,
                                                    $change['old']
                                                );

                                                $newDisplay = $formatAuditValue(
                                                    (string) $field,
                                                    $change['new']
                                                );
                                                ?>

                                                <tr>
                                                    <th scope="row">
                                                        <?= $this->escape(
                                                            (string) $field
                                                        ) ?>
                                                    </th>

                                                    <td>
                                                        <?= $this->escape(
                                                            $oldDisplay
                                                        ) ?>
                                                    </td>

                                                    <td aria-hidden="true">
                                                        →
                                                    </td>

                                                    <td>
                                                        <?= $this->escape(
                                                            $newDisplay
                                                        ) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                <?php elseif ($action === 'create'): ?>
                                    Lid aangemaakt.
                                <?php elseif ($action === 'delete'): ?>
                                    Lid verwijderd.
                                <?php else: ?>
                                    Geen details beschikbaar.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
