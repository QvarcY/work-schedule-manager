<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Pārskats</h1>
    <p class="muted">Pieslēdzies kā <?= e($user['username']) ?> ar lomu <?= e($user['role']) ?>.</p>
</section>

<?php if (($user['role'] ?? '') === 'admin'): ?>
    <section class="panel admin-task-panel <?= empty($adminTasks) ? 'is-clear' : 'has-tasks' ?>">
        <div class="page-title-row">
            <div>
                <h2>Darāmais</h2>
                <?php if (empty($adminTasks)): ?>
                    <p class="muted">Šobrīd nav pieteikumu, kas gaida admina lēmumu.</p>
                <?php else: ?>
                    <p class="muted">Šīs lietas gaida apstiprinājumu vai lēmumu.</p>
                <?php endif; ?>
            </div>
            <?php if (!empty($adminTasks)): ?>
                <span class="admin-task-total"><?= e((string) array_sum(array_column($adminTasks, 'count'))) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($adminTasks)): ?>
            <div class="admin-task-list">
                <?php foreach ($adminTasks as $task): ?>
                    <a class="admin-task-link" href="<?= e(url($task['path'])) ?>">
                        <span><?= e($task['label']) ?></span>
                        <strong><?= e((string) $task['count']) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="grid">
    <div class="panel">
        <h2>Grafiki</h2>
        <p>Skati aktuālos un vēsturiskos grafikus.</p>
        <a class="button secondary" href="<?= e(url('/schedules')) ?>">Atvērt grafikus</a>
    </div>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="panel">
            <h2>Administrēšana</h2>
            <p>Pārvaldi grafikus, moduļus un nākotnes paplašinājumus.</p>
            <a class="button secondary" href="<?= e(url('/modules')) ?>">Atvērt moduļus</a>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Jaunākie grafiki</h2>
    <?php if (empty($latestSchedules)): ?>
        <p>Vēl nav izveidotu grafiku.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nosaukums</th>
                        <th>Mēnesis</th>
                        <th>Statuss</th>
                        <th>Izveidots</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestSchedules as $schedule): ?>
                        <tr>
                            <td><?= e($schedule['schedule_name']) ?></td>
                            <td><?= e($schedule['month']) ?></td>
                            <td><?= e($schedule['status']) ?></td>
                            <td><?= e($schedule['created_at']) ?></td>
                            <td><a href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>">Atvērt</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
