<?php



/*

Component DataTable

Variabelen:

$columns = [

    [
        'label' => 'Naam',
        'field' => 'naam'
    ]

];

$rows = [];

$actions = callable|null;

$emptyTitle

$emptyText

*/

use AEFS\Core\View\Helper\ViewHelpers;

$columns ??= [];

$rows ??= [];

$actions ??= null;

$emptyTitle ??= 'Geen gegevens gevonden';

$emptyText ??= '';

$currentSort = $_GET['sort'] ?? '';

$currentDirection = $_GET['direction'] ?? 'asc';

?>

<div class="table-responsive">

<table class="table">

    <thead>

    <tr>

        <?php foreach ($columns as $column): ?>

            <?php

            $field = $column['field'] ?? '';

            $label = $column['label'] ?? '';

            $sortable = $column['sortable'] ?? true;

            ?>

            <th>

                <?php if ($sortable): ?>

                    <?php

                    $direction =
                        (
                            $currentSort === $field &&
                            $currentDirection === 'asc'
                        )
                        ? 'desc'
                        : 'asc';

                    ?>

                    <a
                        href="?sort=<?= urlencode($field) ?>&direction=<?= $direction ?>"
                        class="table-sort"
                    >

                        <?= htmlspecialchars($label) ?>

                        <?php if ($currentSort === $field): ?>

                            <?= $currentDirection === 'asc'
                                ? icon('chevron-up')
                                : icon('chevron-down') ?>

                        <?php endif; ?>

                    </a>

                <?php else: ?>

                    <?= htmlspecialchars($label) ?>

                <?php endif; ?>

            </th>

        <?php endforeach; ?>

        <?php if ($actions !== null): ?>

            <th width="180">

                Acties

            </th>

        <?php endif; ?>

    </tr>

    </thead>

    <tbody>

    <?php if (empty($rows)): ?>

        <tr>

            <td colspan="<?= count($columns) + ($actions ? 1 : 0) ?>">

                <?= $this->component(

                    'empty-state',

                    [

                        'title' => $emptyTitle,

                        'text' => $emptyText

                    ]

                ) ?>

            </td>

        </tr>

    <?php else: ?>

        <?php foreach ($rows as $row): ?>

            <tr>

                <?php foreach ($columns as $column): ?>

                    <?php

                    $field = $column['field'];

                    ?>

                    <td>

                        <?= $row[$field] ?? '' ?>

                    </td>

                <?php endforeach; ?>

                <?php if ($actions !== null): ?>

                    <td>

                        <?= $actions($row) ?>

                    </td>

                <?php endif; ?>

            </tr>

        <?php endforeach; ?>

    <?php endif; ?>

    </tbody>

</table>

</div>