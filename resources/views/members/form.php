<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var \AEFS\Models\Member|null $lid */

?>

<div class="row">

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'voornaam',

            'label' => 'Voornaam',

            'required' => true,

            'value' => $lid->voornaam ?? ''

        ]) ?>

    </div>

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'achternaam',

            'label' => 'Achternaam',

            'required' => true,

            'value' => $lid->achternaam ?? ''

        ]) ?>

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'email',

            'label' => 'E-mail',

            'type' => 'email',

            'value' => $lid->email ?? ''

        ]) ?>

    </div>

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'telefoon',

            'label' => 'Telefoon',

            'value' => $lid->telefoon ?? ''

        ]) ?>

    </div>

</div>

<div class="row">

    <div class="col-md-8">

        <?= component('input', [

            'name' => 'straat',

            'label' => 'Straat',

            'value' => $lid->straat ?? ''

        ]) ?>

    </div>

    <div class="col-md-4">

        <?= component('input', [

            'name' => 'postcode',

            'label' => 'Postcode',

            'value' => $lid->postcode ?? ''

        ]) ?>

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'gemeente',

            'label' => 'Gemeente',

            'value' => $lid->gemeente ?? ''

        ]) ?>

    </div>

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'land',

            'label' => 'Land',

            'value' => $lid->land ?? 'België'

        ]) ?>

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'geboortedatum',

            'label' => 'Geboortedatum',

            'type' => 'date',

            'value' => $lid->geboortedatum ?? ''

        ]) ?>

    </div>

    <div class="col-md-6">

        <?= component('select', [

            'name' => 'geslacht',

            'label' => 'Geslacht',

            'value' => $lid->geslacht ?? '',

            'options' => [

                '' => '-- Selecteer --',

                'M' => 'Man',

                'V' => 'Vrouw',

                'X' => 'X'

            ]

        ]) ?>

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'rekeningnummer',

            'label' => 'IBAN',

            'value' => $lid->rekeningnummer ?? ''

        ]) ?>

    </div>

    <div class="col-md-6">

        <?= component('input', [

            'name' => 'rijksregisternummer',

            'label' => 'Rijksregisternummer',

            'value' => $lid->rijksregisternummer ?? ''

        ]) ?>

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <?= component('select', [

            'name' => 'tshirtmaat',

            'label' => 'T-shirtmaat',

            'value' => $lid->tshirtmaat ?? '',

            'options' => [

                '' => '-- Selecteer --',

                'XS' => 'XS',

                'S' => 'S',

                'M' => 'M',

                'L' => 'L',

                'XL' => 'XL',

                'XXL' => 'XXL',

                '3XL' => '3XL'

            ]

        ]) ?>

    </div>

    <div class="col-md-6 d-flex align-items-end">

        <?= component('checkbox', [

            'name' => 'actief',

            'label' => 'Actief lid',

            'checked' => $lid->actief ?? true

        ]) ?>

    </div>

</div>

<?= component('textarea', [

    'name' => 'opmerkingen',

    'label' => 'Opmerkingen',

    'rows' => 5,

    'value' => $lid->opmerkingen ?? ''

]) ?>

<?= component('checkbox', [

    'name' => 'gdpr_consent',

    'label' => 'GDPR toestemming',

    'checked' => $lid->gdprConsent ?? false

]) ?>

<hr>

<div class="d-flex justify-content-between">

    <?= component('button', [

        'href' => Url::to('/members'),

        'text' => 'Annuleren',

        'type' => 'secondary'

    ]) ?>

    <?= component('button', [

        'text' => isset($lid)
            ? 'Lid opslaan'
            : 'Lid aanmaken',

        'icon' => 'save',

        'type' => 'success'

    ]) ?>

</div>