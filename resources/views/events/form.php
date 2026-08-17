<?php

declare(strict_types=1);

use AEFS\Core\Url;

/** @var \AEFS\Models\Event|null $event */

$event ??= null;

function old(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

?>

<?= component('card', [

    'title' => 'Evenement',

    'content' => '

<div class="row">

<div class="col-md-8">' .

component('input', [

    'name' => 'titel',

    'label' => 'Titel',

    'required' => true,

    'value' => old('titel', $event?->titel ?? '')

])

. '</div>

<div class="col-md-4">' .

component('checkbox', [

    'name' => 'actief',

    'label' => 'Actief',

    'checked' => old('actief', $event?->actief ?? true)

])

. '</div>

</div>

<div class="row">

<div class="col-md-12">' .

component('textarea', [

    'name' => 'omschrijving',

    'label' => 'Omschrijving',

    'rows' => 5,

    'value' => old('omschrijving', $event?->omschrijving ?? '')

])

. '</div>

</div>

<div class="row">

<div class="col-md-6">' .

component('input', [

    'name' => 'locatie',

    'label' => 'Locatie',

    'value' => old('locatie', $event?->locatie ?? '')

])

. '</div>

<div class="col-md-6">' .

component('input', [

    'name' => 'max_deelnemers',

    'label' => 'Maximum deelnemers',

    'type' => 'number',

    'min' => 1,

    'value' => old('max_deelnemers', $event?->maxDeelnemers ?? '')

])

. '</div>

</div>

<div class="row">

<div class="col-md-6">' .

component('input', [

    'name' => 'start_datum',

    'label' => 'Startdatum',

    'type' => 'date',

    'required' => true,

    'value' => old('start_datum', $event?->startDatum ?? '')

])

. '</div>

<div class="col-md-6">' .

component('input', [

    'name' => 'eind_datum',

    'label' => 'Einddatum',

    'type' => 'date',

    'value' => old('eind_datum', $event?->eindDatum ?? '')

])

. '</div>

</div>

<hr>

<div class="d-flex justify-content-between">' .

component('button', [

    'href' => Url::to('/events'),

    'text' => 'Annuleren',

    'type' => 'secondary'

])

.

component('button', [

    'text' => $event
        ? 'Evenement opslaan'
        : 'Evenement aanmaken',

    'icon' => 'save',

    'type' => 'success'

])

. '</div>'

]) ?>