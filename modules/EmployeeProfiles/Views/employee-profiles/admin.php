<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('employee_profiles.admin.title')) ?></h1>
    <p class="muted"><?= e(t('employee_profiles.admin.description')) ?></p>
</section>

<section class="panel">
    <?php if (empty($employees)): ?>
        <p><?= e(t('employee_profiles.admin.empty')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('users.fields.first_name')) ?></th>
                        <th><?= e(t('users.fields.last_name')) ?></th>
                        <th><?= e(t('users.fields.email')) ?></th>
                        <th><?= e(t('users.fields.phone')) ?></th>
                        <th><?= e(t('employee_profiles.admin.schedule_visibility')) ?></th>
                        <th><?= e(t('employee_profiles.admin.registered')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?= e($employee['first_name'] ?? '') ?></td>
                            <td><?= e($employee['last_name'] ?? '') ?></td>
                            <td><?= e($employee['email'] ?? $employee['username']) ?></td>
                            <td><?= e($employee['phone'] ?? '') ?></td>
                            <td>
                                <form method="post" action="<?= e(url('/employee-profiles/admin/visibility')) ?>" class="inline-select-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= e((string) $employee['id']) ?>">
                                    <select name="schedule_view_mode">
                                        <?php foreach ([
                                            'full' => t('users.schedule_view.full'),
                                            'own' => t('users.schedule_view.own'),
                                            'day' => t('users.schedule_view.day'),
                                            'night' => t('users.schedule_view.night'),
                                        ] as $mode => $label): ?>
                                            <option value="<?= e($mode) ?>" <?= ($employee['schedule_view_mode'] ?? 'full') === $mode ? 'selected' : '' ?>>
                                                <?= e($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button secondary" type="submit"><?= e(t('common.save')) ?></button>
                                </form>
                            </td>
                            <td><?= e((string) $employee['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
