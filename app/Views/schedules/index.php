<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Grafiki</h1>
    <?php if ($isAdmin): ?>
        <p><a class="button" href="<?= e(url('/schedules/create')) ?>">Izveidot grafika sagatavi</a></p>
    <?php else: ?>
        <p class="muted">Pieejamie publicētie grafiki.</p>
    <?php endif; ?>
</section>

<?php
$visibleSchedules = $isAdmin
    ? array_values(array_filter($schedules, static fn (array $schedule): bool => ($schedule['status'] ?? '') !== 'archived'))
    : $schedules;
$archivedSchedules = $isAdmin
    ? array_values(array_filter($schedules, static fn (array $schedule): bool => ($schedule['status'] ?? '') === 'archived'))
    : [];
?>

<section class="panel">
    <h2><?= $isAdmin ? 'Aktīvie grafiki' : 'Grafiku saraksts' ?></h2>
    <?php if (empty($visibleSchedules)): ?>
        <p>Nav saglabātu grafiku.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nosaukums</th>
                        <th>Mēnesis</th>
                        <?php if ($isAdmin): ?>
                            <th>Statuss</th>
                        <?php endif; ?>
                        <th>Atjaunots</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visibleSchedules as $schedule): ?>
                        <tr>
                            <td><?= e($schedule['schedule_name']) ?></td>
                            <td><?= e($schedule['month']) ?></td>
                            <?php if ($isAdmin): ?>
                                <td><?= e($schedule['status']) ?></td>
                            <?php endif; ?>
                            <td><?= e($schedule['updated_at']) ?></td>
                            <td><a href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>">Atvērt</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($isAdmin && !empty($archivedSchedules)): ?>
    <section class="panel">
        <details class="archive-panel">
            <summary>Arhivētie grafiki (<?= e((string) count($archivedSchedules)) ?>)</summary>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nosaukums</th>
                            <th>Mēnesis</th>
                            <th>Atjaunots</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivedSchedules as $schedule): ?>
                            <tr>
                                <td><?= e($schedule['schedule_name']) ?></td>
                                <td><?= e($schedule['month']) ?></td>
                                <td><?= e($schedule['updated_at']) ?></td>
                                <td><a href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>">Atvērt</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
<?php endif; ?>
