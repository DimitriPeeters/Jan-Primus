<?php

declare(strict_types=1);

use AEFS\Core\Url;

/**
 * @var AEFS\Models\Event $event
 * @var AEFS\Models\Shift[] $shifts
 * @var AEFS\Models\ShiftType[] $shiftTypes
 */

?>

<?= component('page-header', [

    'title' => $event->titel,

    'subtitle' => 'Shiftplanning',

    'actions' =>

        component('button', [

            'text' => 'Nieuwe shift',

            'icon' => 'plus',

            'type' => 'primary',

            'href' => Url::to('/shifts/create?event=' . $event->eventId)

        ])

]) ?>

<div class="row">

    <div class="col-lg-8">

        <?php foreach ($shifts as $shift): ?>

            <div class="card shadow-sm mb-3">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <span
                                class="badge"
                                style="
                                    background: <?= $shift->typeKleur ?>;
                                    color:#fff;
                                "
                            >

                                <?= htmlspecialchars($shift->typeNaam) ?>

                            </span>

                            <h5 class="mt-3 mb-1">

                                <?= htmlspecialchars($shift->tijdvak()) ?>

                            </h5>

                            <div class="text-muted">

                                <?= date(
                                    'd/m/Y',
                                    strtotime($shift->shiftDatum)
                                ) ?>

                            </div>

                        </div>

                        <div class="text-end">

                            <?php if ($shift->vergrendeld): ?>

                                <span class="badge bg-danger">

                                    LOCKED

                                </span>

                            <?php endif; ?>

                            <br><br>

                            <strong>

                                <?= $shift->bezetting() ?>

                            </strong>

                        </div>

                    </div>

                    <br>

                    <?php

                    $percentage = 0;

                    if ($shift->maxPersonen > 0) {

                        $percentage = min(
                            100,
                            ($shift->aantalBevestigd / $shift->maxPersonen) * 100
                        );

                    }

                    ?>

                    <div class="progress">

                        <div

                            class="progress-bar"

                            role="progressbar"

                            style="width: <?= $percentage ?>%;"

                        >

                            <?= $shift->aantalBevestigd ?>

                        </div>

                    </div>

                    <br>

                    <div class="row text-center">

                        <div class="col">

                            <strong>

                                <?= $shift->maxPersonen ?>

                            </strong>

                            <br>

                            gevraagd

                        </div>

                        <div class="col">

                            <strong class="text-success">

                                <?= $shift->aantalBevestigd ?>

                            </strong>

                            <br>

                            bevestigd

                        </div>

                        <div class="col">

                            <strong class="text-warning">

                                <?= $shift->aantalWachtend ?>

                            </strong>

                            <br>

                            wachtend

                        </div>

                        <div class="col">

                            <strong class="text-info">

                                <?= $shift->aantalReserve ?>

                            </strong>

                            <br>

                            reserve

                        </div>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2">

                        <?= component('button', [

                            'href' => Url::to('/shifts/' . $shift->shiftId),

                            'icon' => 'eye',

                            'type' => 'secondary',

                            'size' => 'sm'

                        ]) ?>

                        <?= component('button', [

                            'href' => Url::to('/shifts/' . $shift->shiftId . '/edit'),

                            'icon' => 'edit',

                            'type' => 'warning',

                            'size' => 'sm'

                        ]) ?>

                        <?php if ($shift->vergrendeld): ?>

                            <?= component('button', [

                                'href' => Url::to('/shifts/' . $shift->shiftId . '/unlock'),

                                'icon' => 'unlock',

                                'type' => 'success',

                                'size' => 'sm'

                            ]) ?>

                        <?php else: ?>

                            <?= component('button', [

                                'href' => Url::to('/shifts/' . $shift->shiftId . '/lock'),

                                'icon' => 'lock',

                                'type' => 'danger',

                                'size' => 'sm'

                            ]) ?>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="col-lg-4">

        <div class="card shadow-sm sticky-top">

            <div class="card-header">

                Dashboard

            </div>

            <div class="card-body">

                <?php

                $totaal = count($shifts);

                $bevestigd = 0;
                $wachtend = 0;
                $reserve = 0;
                $onderbezet = 0;
                $locked = 0;

                foreach ($shifts as $shift) {

                    $bevestigd += $shift->aantalBevestigd;
                    $wachtend += $shift->aantalWachtend;
                    $reserve += $shift->aantalReserve;

                    if ($shift->isOnderbezet()) {
                        $onderbezet++;
                    }

                    if ($shift->vergrendeld) {
                        $locked++;
                    }

                }

                ?>

                <table class="table table-sm">

                    <tr>

                        <th>Shiften</th>

                        <td><?= $totaal ?></td>

                    </tr>

                    <tr>

                        <th>Bevestigd</th>

                        <td><?= $bevestigd ?></td>

                    </tr>

                    <tr>

                        <th>Wachtend</th>

                        <td><?= $wachtend ?></td>

                    </tr>

                    <tr>

                        <th>Reserve</th>

                        <td><?= $reserve ?></td>

                    </tr>

                    <tr>

                        <th>Onderbezet</th>

                        <td class="text-danger">

                            <?= $onderbezet ?>

                        </td>

                    </tr>

                    <tr>

                        <th>Locked</th>

                        <td>

                            <?= $locked ?>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>