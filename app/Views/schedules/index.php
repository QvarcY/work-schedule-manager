<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('schedules.title')) ?></h1>
    <?php if ($isAdmin): ?>
        <p><a class="button" href="<?= e(url('/schedules/create')) ?>"><?= e(t('schedules.create_template')) ?></a></p>
    <?php else: ?>
        <p class="muted"><?= e(t('schedules.published_description')) ?></p>
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
    <h2><?= e($isAdmin ? t('schedules.active') : t('schedules.list')) ?></h2>
    <?php if (empty($visibleSchedules)): ?>
        <p><?= e(t('schedules.empty')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('schedules.fields.name')) ?></th>
                        <th><?= e(t('schedules.fields.month')) ?></th>
                        <?php if ($isAdmin): ?>
                            <th><?= e(t('schedules.fields.status')) ?></th>
                        <?php endif; ?>
                        <th><?= e(t('schedules.fields.updated')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visibleSchedules as $schedule): ?>
                        <tr>
                            <td><?= e($schedule['schedule_name']) ?></td>
                            <td><?= e($schedule['month']) ?></td>
                            <?php if ($isAdmin): ?>
                                <td><?= e(t('schedule.status.' . $schedule['status'], [], (string) $schedule['status'])) ?></td>
                            <?php endif; ?>
                            <td><?= e($schedule['updated_at']) ?></td>
                            <td><a href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>"><?= e(t('common.open')) ?></a></td>
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
            <summary><?= e(t('schedules.archived_count', ['count' => count($archivedSchedules)])) ?></summary>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= e(t('schedules.fields.name')) ?></th>
                            <th><?= e(t('schedules.fields.month')) ?></th>
                            <th><?= e(t('schedules.fields.updated')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivedSchedules as $schedule): ?>
                            <tr>
                                <td><?= e($schedule['schedule_name']) ?></td>
                                <td><?= e($schedule['month']) ?></td>
                                <td><?= e($schedule['updated_at']) ?></td>
                                <td><a href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>"><?= e(t('common.open')) ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
<?php endif; ?>
