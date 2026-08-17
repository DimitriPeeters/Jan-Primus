<?php

declare(strict_types=1);

/** @var array $logs */

$logs ??= [];

?>

<div class="card">

    <div class="card-header">

        <div class="card-title">

            Auditlog

        </div>

    </div>

    <div class="card-body">

        <?php if (empty($logs)): ?>

            <?= component('empty-state', [

                'title' => 'Nog geen historiek',

                'text' => 'Voor dit record zijn nog geen wijzigingen geregistreerd.'

            ]) ?>

        <?php else: ?>

            <table class="table">

                <thead>

                <tr>

                    <th width="170">

                        Datum

                    </th>

                    <th width="120">

                        Actie

                    </th>

                    <th width="120">

                        Gebruiker

                    </th>

                    <th>

                        Wijzigingen

                    </th>

                </tr>

                </thead>

                <tbody>

                <?php foreach ($logs as $log): ?>

                    <?php

                    $old = json_decode(
                        $log['old_values'] ?? '[]',
                        true
                    ) ?? [];

                    $new = json_decode(
                        $log['new_values'] ?? '[]',
                        true
                    ) ?? [];

                    ?>

                    <tr>

                        <td>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime($log['created_at'])
                            ) ?>

                        </td>

                        <td>

                            <?php

                            switch ($log['action']) {

                                case 'create':

                                    echo '<span class="badge badge-success">Aangemaakt</span>';

                                    break;

                                case 'update':

                                    echo '<span class="badge badge-warning">Gewijzigd</span>';

                                    break;

                                case 'delete':

                                    echo '<span class="badge badge-danger">Verwijderd</span>';

                                    break;

                            }

                            ?>

                        </td>

                        <td>

                            <?= $log['user_id'] ?? '-' ?>

                        </td>

                        <td>

                            <?php if ($log['action'] === 'update'): ?>

                                <table
                                    class="table table-sm"
                                    style="margin:0;"
                                >

                                    <?php foreach ($new as $veld => $waarde): ?>

                                        <?php

                                        $oudeWaarde = $old[$veld] ?? null;

                                        if ($oudeWaarde == $waarde) {
                                            continue;
                                        }

                                        ?>

                                        <tr>

                                            <td
                                                style="width:180px;font-weight:bold;"
                                            >

                                                <?= htmlspecialchars($veld) ?>

                                            </td>

                                            <td>

                                                <?= htmlspecialchars(
                                                    (string)$oudeWaarde
                                                ) ?>

                                            </td>

                                            <td
                                                style="width:40px;text-align:center;"
                                            >

                                                →

                                            </td>

                                            <td>

                                                <?= htmlspecialchars(
                                                    (string)$waarde
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </table>

                            <?php elseif ($log['action'] === 'create'): ?>

                                Lid aangemaakt.

                            <?php else: ?>

                                Lid verwijderd.

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>