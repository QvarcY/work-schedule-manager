<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('notifications.admin.title')) ?></h1>
    <p class="muted"><?= e(t('notifications.admin.description')) ?></p>
</section>

<section class="panel">
    <h2><?= e(t('notifications.admin.new')) ?></h2>
    <form method="post" action="<?= e(url('/notifications/admin/send')) ?>" class="notification-send-form">
        <?= csrf_field() ?>
        <input name="title" type="text" placeholder="<?= e(t('notifications.admin.fields.title')) ?>" required>
        <select name="type">
            <?php foreach ($types as $type => $label): ?>
                <option value="<?= e($type) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="target" data-notification-target>
            <option value="all"><?= e(t('notifications.admin.targets.all')) ?></option>
            <option value="role"><?= e(t('notifications.admin.targets.role')) ?></option>
            <option value="user"><?= e(t('notifications.admin.targets.user')) ?></option>
        </select>
        <select name="role">
            <?php foreach ($roles as $role): ?>
                <option value="<?= e($role['code']) ?>"><?= e($role['label']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="user_id">
            <?php foreach ($users as $user): ?>
                <option value="<?= e((string) $user['id']) ?>">
                    <?= e($user['username']) ?><?= trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')) !== '' ? ' · ' . e(trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''))) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <textarea name="body" rows="4" placeholder="<?= e(t('notifications.admin.fields.body')) ?>" required></textarea>
        <button class="button" type="submit"><?= e(t('common.send')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('notifications.admin.recent')) ?></h2>
    <?php if (empty($recent)): ?>
        <p><?= e(t('notifications.admin.empty')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('notifications.admin.fields.title')) ?></th>
                        <th><?= e(t('notifications.admin.fields.type')) ?></th>
                        <th><?= e(t('notifications.admin.fields.created')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $notification): ?>
                        <tr>
                            <td><?= e($notification['title']) ?></td>
                            <td><?= e($types[$notification['type']] ?? $notification['type']) ?></td>
                            <td><?= e((string) $notification['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
