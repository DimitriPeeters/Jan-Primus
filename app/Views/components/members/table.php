<?php



use AEFS\Core\View\Helper\ViewHelpers;

/** @var AEFS\Models\Member[] $leden */

$leden ??= [];

?>

<?php if (empty($leden)): ?>

    <?= $this->component('empty-state', [

        'title' => 'Geen leden gevonden',

        'text'  => 'Er zijn momenteel geen leden beschikbaar.'

    ]) ?>

<?php else: ?>

<table class="table table-hover align-middle">

    <thead>

    <tr>

        <th>Naam</th>

        <th>E-mail</th>

        <th>Telefoon</th>

        <th>Gemeente</th>

        <th class="text-center">Status</th>

        <th class="text-end" style="width:220px;">Acties</th>

    </tr>

    </thead>

    <tbody>

    <?php foreach ($leden as $lid): ?>

        <tr>

            <td>

                <strong><?= htmlspecialchars($lid->fullName()) ?></strong>

            </td>

            <td>

                <?= htmlspecialchars($lid->email ?? '-') ?>

            </td>

            <td>

                <?= htmlspecialchars($lid->telefoon ?? '-') ?>

            </td>

            <td>

                <?= htmlspecialchars($lid->gemeente ?? '-') ?>

            </td>

            <td class="text-center">

                <?php if ($lid->actief): ?>

                    <span class="badge bg-success">
                        Actief
                    </span>

                <?php else: ?>

                    <span class="badge bg-danger">
                        Inactief
                    </span>

                <?php endif; ?>

            </td>

            <td class="text-end">

                <?= $this->component('button', [

                    'href' => $helpers->url->to('/members/' . $lid->lidId),

                    'icon' => 'eye',

                    'type' => 'secondary',

                    'size' => 'sm',

                    'title' => 'Bekijken'

                ]) ?>

                <?= $this->component('button', [

                    'href' => $helpers->url->to('/members/' . $lid->lidId . '/edit'),

                    'icon' => 'edit',

                    'type' => 'warning',

                    'size' => 'sm',

                    'title' => 'Wijzigen'

                ]) ?>

                <?= $this->component('button', [

                    'href' => $helpers->url->to('/members/' . $lid->lidId . '/delete'),

                    'icon' => 'trash',

                    'type' => 'danger',

                    'size' => 'sm',

                    'confirm' => 'Dit lid verwijderen?'

                ]) ?>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

<?php endif; ?>