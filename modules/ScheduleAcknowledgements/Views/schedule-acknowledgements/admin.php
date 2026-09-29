<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('acknowledgements.admin.title')) ?></h1>
    <p class="muted"><?= e(t('acknowledgements.admin.description')) ?></p>
</section>

<section class="panel">
    <form method="get" action="<?= e(url('/schedule-acknowledgements/admin')) ?>" class="compact-form">
        <select name="schedule_id" required>
            <option value=""><?= e(t('acknowledgements.admin.select_schedule')) ?></option>
            <?php foreach ($schedules as $schedule): ?>
                <option value="<?= e((string) $schedule['id']) ?>" <?= (int) $selectedScheduleId === (int) $schedule['id'] ? 'selected' : '' ?>>
                    <?= e($schedule['schedule_name']) ?> / <?= e($schedule['month']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit"><?= e(t('common.view')) ?></button>
    </form>
</section>

<?php if ($selectedScheduleId > 0): ?>
    <section class="panel">
        <h2><?= e(t('acknowledgements.admin.status')) ?></h2>
        <?php if (empty($rows)): ?>
            <p><?= e(t('acknowledgements.admin.empty')) ?></p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= e(t('schedules.employee')) ?></th>
                            <th><?= e(t('acknowledgements.admin.schedule_name')) ?></th>
                            <th><?= e(t('schedules.fields.status')) ?></th>
                            <th><?= e(t('acknowledgements.admin.acknowledged')) ?></th>
                            <th><?= e(t('common.comment')) ?></th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                                $fullName = $fullName !== '' ? $fullName : (string) $row['username'];
                                $acknowledged = (int) ($row['acknowledgement_current'] ?? 0) === 1;
                            ?>
                            <tr class="<?= $acknowledged ? 'journal-admin-row' : 'journal-user-row' ?>">
                                <td><strong><?= e($fullName) ?></strong></td>
                                <td><?= e($row['schedule_name'] ?? '') ?></td>
                                <td>
                                    <span class="journal-action <?= $acknowledged ? 'admin' : 'user' ?>">
                                        <?= e($acknowledged ? t('acknowledgements.status.acknowledged') : t('acknowledgements.status.waiting')) ?>
                                    </span>
                                </td>
                                <td><?= e($row['acknowledged_at'] ?: '-') ?></td>
                                <td><?= e($row['comment'] ?: '') ?></td>
                                <td><?= e($row['ip_address'] ?: '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
