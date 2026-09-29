<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('dashboard.title')) ?></h1>
    <p class="muted"><?= e(t('dashboard.signed_in', [
        'username' => $user['username'],
        'role' => t('roles.' . ($user['role'] ?? 'user'), [], (string) ($user['role'] ?? 'user')),
    ])) ?></p>
</section>

<?php if (($user['role'] ?? '') === 'admin'): ?>
    <section class="panel admin-task-panel <?= empty($adminTasks) ? 'is-clear' : 'has-tasks' ?>">
        <div class="page-title-row">
            <div>
                <h2><?= e(t('dashboard.tasks.title')) ?></h2>
                <?php if (empty($adminTasks)): ?>
                    <p class="muted"><?= e(t('dashboard.tasks.empty')) ?></p>
                <?php else: ?>
                    <p class="muted"><?= e(t('dashboard.tasks.pending')) ?></p>
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
        <h2><?= e(t('dashboard.schedules.title')) ?></h2>
        <p><?= e(t('dashboard.schedules.description')) ?></p>
        <a class="button secondary" href="<?= e(url('/schedules')) ?>"><?= e(t('dashboard.schedules.open')) ?></a>
    </div>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="panel">
            <h2><?= e(t('dashboard.admin.title')) ?></h2>
            <p><?= e(t('dashboard.admin.description')) ?></p>
            <a class="button secondary" href="<?= e(url('/modules')) ?>"><?= e(t('dashboard.admin.open_modules')) ?></a>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2><?= e(t('dashboard.latest.title')) ?></h2>
    <?php if (empty($latestSchedules)): ?>
        <p><?= e(t('dashboard.latest.empty')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('dashboard.table.name')) ?></th>
                        <th><?= e(t('dashboard.table.month')) ?></th>
                        <th><?= e(t('dashboard.table.status')) ?></th>
                        <th><?= e(t('dashboard.table.created')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestSchedules as $schedule): ?>
                        <tr>
                            <td><?= e($schedule['schedule_name']) ?></td>
                            <td><?= e($schedule['month']) ?></td>
                            <td><?= e(t('schedule.status.' . $schedule['status'], [], (string) $schedule['status'])) ?></td>
                            <td><?= e($schedule['created_at']) ?></td>
                            <td><a href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>"><?= e(t('common.open')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
