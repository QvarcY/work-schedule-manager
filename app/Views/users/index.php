<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$roleOptions = [];
foreach ($roles as $role) {
    $roleOptions[(string) $role['code']] = (string) $role['label'];
}

$scheduleViewOptions = [
    'full' => t('users.schedule_view.full'),
    'own' => t('users.schedule_view.own'),
    'day' => t('users.schedule_view.day'),
    'night' => t('users.schedule_view.night'),
];
?>

<section class="panel">
    <h1><?= e(t('users.title')) ?></h1>
    <p class="muted"><?= e(t('users.description')) ?></p>
</section>

<section class="panel">
    <h2><?= e(t('users.create.title')) ?></h2>
    <form method="post" action="<?= e(url('/users/store')) ?>" class="user-create-grid">
        <?= csrf_field() ?>
        <input name="username" type="text" placeholder="<?= e(t('users.fields.username')) ?>" autocomplete="username" required>
        <input name="password" type="password" placeholder="<?= e(t('users.fields.password')) ?>" required>
        <select name="role">
            <?php foreach ($roleOptions as $code => $label): ?>
                <option value="<?= e($code) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="first_name" type="text" placeholder="<?= e(t('users.fields.first_name')) ?>">
        <input name="last_name" type="text" placeholder="<?= e(t('users.fields.last_name')) ?>">
        <input name="email" type="email" placeholder="<?= e(t('users.fields.email')) ?>">
        <input name="phone" type="text" placeholder="<?= e(t('users.fields.phone')) ?>">
        <select name="schedule_view_mode">
            <?php foreach ($scheduleViewOptions as $mode => $label): ?>
                <option value="<?= e($mode) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="checkbox-pill">
            <input type="checkbox" name="can_be_scheduled" value="1">
            <span><?= e(t('users.fields.schedulable')) ?></span>
        </label>
        <label class="checkbox-pill">
            <input type="checkbox" name="show_hours_summary" value="1">
            <span><?= e(t('users.fields.hours_summary')) ?></span>
        </label>
        <button class="button" type="submit"><?= e(t('common.add')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('users.existing')) ?></h2>
    <div class="user-list">
        <?php foreach ($users as $user): ?>
            <details class="user-list-item">
                <summary>
                    <span>
                        <strong><?= e($user['username']) ?></strong>
                        <small>
                            <?= e(trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')) ?: t('users.no_profile_name')) ?>
                        </small>
                    </span>
                    <span class="role-badge"><?= e($roleOptions[$user['role']] ?? $user['role']) ?></span>
                    <?php if ((int) ($user['can_be_scheduled'] ?? 0) === 1): ?>
                        <span class="role-badge soft"><?= e(t('users.fields.schedulable')) ?></span>
                    <?php endif; ?>
                    <span class="button secondary user-edit-button"><?= e(t('common.edit')) ?></span>
                </summary>

                <div class="user-edit-panel">
                    <form method="post" action="<?= e(url('/users/update')) ?>" class="user-edit-grid">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">
                        <input name="username" type="text" value="<?= e($user['username']) ?>" autocomplete="username" required>
                        <select name="role">
                            <?php foreach ($roleOptions as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= $user['role'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="first_name" type="text" value="<?= e($user['first_name'] ?? '') ?>" placeholder="<?= e(t('users.fields.first_name')) ?>">
                        <input name="last_name" type="text" value="<?= e($user['last_name'] ?? '') ?>" placeholder="<?= e(t('users.fields.last_name')) ?>">
                        <input name="email" type="email" value="<?= e($user['email'] ?? '') ?>" placeholder="<?= e(t('users.fields.email')) ?>">
                        <input name="phone" type="text" value="<?= e($user['phone'] ?? '') ?>" placeholder="<?= e(t('users.fields.phone')) ?>">
                        <select name="schedule_view_mode">
                            <?php foreach ($scheduleViewOptions as $mode => $label): ?>
                                <option value="<?= e($mode) ?>" <?= ($user['schedule_view_mode'] ?? 'full') === $mode ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="password" type="password" placeholder="<?= e(t('users.fields.new_password')) ?>">
                        <label class="checkbox-pill">
                            <input type="checkbox" name="can_be_scheduled" value="1" <?= (int) ($user['can_be_scheduled'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <span><?= e(t('users.fields.schedulable')) ?></span>
                        </label>
                        <label class="checkbox-pill">
                            <input type="checkbox" name="show_hours_summary" value="1" <?= (int) ($user['show_hours_summary'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <span><?= e(t('users.fields.hours_summary')) ?></span>
                        </label>
                        <div class="user-edit-actions">
                            <button class="button secondary" type="submit"><?= e(t('common.save')) ?></button>
                        </div>
                    </form>
                    <form method="post" action="<?= e(url('/users/delete')) ?>" data-confirm="<?= e(t('users.delete.confirm')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">
                        <button class="link-button" type="submit"><?= e(t('common.delete')) ?></button>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <h2><?= e(t('users.permissions.title')) ?></h2>
    <p class="muted"><?= e(t('users.permissions.description')) ?></p>
    <div class="role-permissions-grid">
        <?php foreach ($roles as $role): ?>
            <?php $roleCode = (string) $role['code']; ?>
            <form method="post" action="<?= e(url('/roles/permissions')) ?>" class="role-card">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="<?= e($roleCode) ?>">
                <h3><?= e($role['label']) ?></h3>
                <div class="permission-list">
                    <?php foreach ($permissions as $permission): ?>
                        <?php $permissionCode = (string) $permission['code']; ?>
                        <label>
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="<?= e($permissionCode) ?>"
                                <?= !empty($rolePermissionMap[$roleCode][$permissionCode]) ? 'checked' : '' ?>
                            >
                            <span><?= e($permission['label']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="button secondary" type="submit"><?= e(t('users.permissions.save')) ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</section>
