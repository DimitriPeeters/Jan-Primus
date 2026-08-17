<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var AEFS\Models\Member $lid */
/** @var array $logs */

?>

<?= component('page-header', [

    'title' => $lid->fullName(),

    'subtitle' => 'Ledenfiche',

    'actions' =>

        component('button', [

            'text' => 'Wijzigen',

            'icon' => 'edit',

            'type' => 'warning',

            'href' => Url::to('/members/' . $lid->lidId . '/edit')

        ])

]) ?>

<div class="row">

    <div class="col-lg-6">

        <?= component('card', [

            'title' => 'Persoonsgegevens',

            'content' => '

<table class="table table-sm mb-0">

<tr><th width="180">Voornaam</th><td>' . htmlspecialchars($lid->voornaam) . '</td></tr>

<tr><th>Achternaam</th><td>' . htmlspecialchars($lid->achternaam) . '</td></tr>

<tr><th>E-mail</th><td>' . htmlspecialchars($lid->email) . '</td></tr>

<tr><th>Telefoon</th><td>' . htmlspecialchars($lid->telefoon) . '</td></tr>

<tr><th>Geboortedatum</th><td>' . htmlspecialchars((string)$lid->geboortedatum) . '</td></tr>

<tr><th>Geslacht</th><td>' . htmlspecialchars((string)$lid->geslacht) . '</td></tr>

</table>

'

        ]) ?>

    </div>

    <div class="col-lg-6">

        <?= component('card', [

            'title' => 'Adres',

            'content' => '

<table class="table table-sm mb-0">

<tr><th width="180">Straat</th><td>' . htmlspecialchars($lid->straat) . '</td></tr>

<tr><th>Postcode</th><td>' . htmlspecialchars($lid->postcode) . '</td></tr>

<tr><th>Gemeente</th><td>' . htmlspecialchars($lid->gemeente) . '</td></tr>

<tr><th>Land</th><td>' . htmlspecialchars((string)$lid->land) . '</td></tr>

</table>

'

        ]) ?>

    </div>

</div>

<br>

<div class="row">

    <div class="col-lg-6">

        <?= component('card', [

            'title' => 'Lidmaatschap',

            'content' => '

<table class="table table-sm mb-0">

<tr><th width="180">Actief</th><td>' .

($lid->actief
    ? '<span class="badge bg-success">Ja</span>'
    : '<span class="badge bg-danger">Nee</span>')

. '</td></tr>

<tr><th>GDPR</th><td>' .

($lid->gdprConsent
    ? '<span class="badge bg-success">Ja</span>'
    : '<span class="badge bg-secondary">Nee</span>')

. '</td></tr>

<tr><th>T-shirt</th><td>' . htmlspecialchars((string)$lid->tshirtmaat) . '</td></tr>

</table>

'

        ]) ?>

    </div>

    <div class="col-lg-6">

        <?= component('card', [

            'title' => 'Opmerkingen',

            'content' => nl2br(
                htmlspecialchars(
                    $lid->opmerkingen ?? '-'
                )
            )

        ]) ?>

    </div>

</div>

<br>

<?= component('audit-log', [

    'logs' => $logs ?? []

]) ?>

<br>

<div class="d-flex justify-content-between">

    <?= component('button', [

        'href' => Url::to('/members'),

        'text' => 'Terug',

        'type' => 'secondary'

    ]) ?>

    <?= component('button', [

        'href' => Url::to('/members/' . $lid->lidId . '/edit'),

        'text' => 'Wijzigen',

        'icon' => 'edit',

        'type' => 'warning'

    ]) ?>

</div>