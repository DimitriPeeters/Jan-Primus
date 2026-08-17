<?php

declare(strict_types=1);

?>

<h1>Dashboard</h1>

<p>Welkom in AEFS v2.</p>

<section>
    <h2>Statistieken</h2>

    <div>
        <p><strong>Leden:</strong> <?= (int) $statistics['members'] ?></p>
        <p><strong>Gebruikers:</strong> <?= (int) $statistics['users'] ?></p>
        <p><strong>Evenementen:</strong> <?= (int) $statistics['events'] ?></p>
        <p><strong>Open shifts:</strong> <?= (int) $statistics['shifts'] ?></p>
    </div>
</section>

<section>
    <h2>Laatste leden</h2>

    <table>
        <thead>
            <tr>
                <th>Naam</th>
                <th>Gemeente</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($latestMembers === []): ?>
                <tr>
                    <td colspan="2">Geen leden gevonden.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($latestMembers as $member): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(($member['voornaam'] ?? '') . ' ' . ($member['achternaam'] ?? '')) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($member['gemeente'] ?? '-') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section>
    <h2>Komende evenementen</h2>

    <table>
        <thead>
            <tr>
                <th>Evenement</th>
                <th>Datum</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($upcomingEvents === []): ?>
                <tr>
                    <td colspan="2">Geen evenementen gepland.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($upcomingEvents as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['titel'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($event['startdatum'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section>
    <h2>Openstaande shifts</h2>

    <table>
        <thead>
            <tr>
                <th>Shift</th>
                <th>Datum</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($openShifts === []): ?>
                <tr>
                    <td colspan="2">Geen openstaande shifts.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($openShifts as $shift): ?>
                    <tr>
                        <td><?= htmlspecialchars($shift['naam'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($shift['shift_datum'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>