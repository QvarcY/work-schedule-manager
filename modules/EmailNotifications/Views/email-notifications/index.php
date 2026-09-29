<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('email_notifications.title')) ?></h1>
    <p class="muted"><?= e(t('email_notifications.description')) ?></p>
</section>

<section class="panel">
    <h2><?= e(t('email_notifications.settings.title')) ?></h2>
    <form method="post" action="<?= e(url('/email-notifications/settings')) ?>" class="user-create-grid">
        <?= csrf_field() ?>
        <label class="checkbox-pill">
            <input type="checkbox" name="enabled" value="1" <?= ($settings['enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            <span><?= e(t('email_notifications.settings.enabled')) ?></span>
        </label>
        <input name="from_email" type="email" value="<?= e($settings['from_email'] ?? '') ?>" placeholder="<?= e(t('email_notifications.settings.from_email')) ?>" required>
        <input name="from_name" type="text" value="<?= e($settings['from_name'] ?? '') ?>" placeholder="<?= e(t('email_notifications.settings.from_name')) ?>">
        <input name="reply_to" type="email" value="<?= e($settings['reply_to'] ?? '') ?>" placeholder="<?= e(t('email_notifications.settings.reply_to')) ?>">
        <input name="subject_prefix" type="text" value="<?= e($settings['subject_prefix'] ?? '') ?>" placeholder="<?= e(t('email_notifications.settings.subject_prefix')) ?>">
        <button class="button" type="submit"><?= e(t('common.save')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('email_notifications.test.title')) ?></h2>
    <form method="post" action="<?= e(url('/email-notifications/test')) ?>" class="compact-form">
        <?= csrf_field() ?>
        <input name="test_email" type="email" placeholder="<?= e(t('email_notifications.test.recipient')) ?>" required>
        <button class="button secondary" type="submit"><?= e(t('email_notifications.test.submit')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('email_notifications.deliveries.title')) ?></h2>
    <?php if (empty($recentDeliveries)): ?>
        <p><?= e(t('email_notifications.deliveries.empty')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('email_notifications.deliveries.time')) ?></th>
                        <th><?= e(t('email_notifications.deliveries.user')) ?></th>
                        <th><?= e(t('email_notifications.deliveries.email')) ?></th>
                        <th><?= e(t('email_notifications.deliveries.notification')) ?></th>
                        <th><?= e(t('email_notifications.deliveries.status')) ?></th>
                        <th><?= e(t('email_notifications.deliveries.error')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentDeliveries as $delivery): ?>
                        <tr>
                            <td><?= e((string) $delivery['created_at']) ?></td>
                            <td><?= e($delivery['username']) ?></td>
                            <td><?= e($delivery['email'] ?: '-') ?></td>
                            <td><?= e($delivery['title']) ?></td>
                            <td><?= e($delivery['status']) ?></td>
                            <td><?= e($delivery['error_message'] ?: '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
