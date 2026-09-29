<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <div class="page-title-row">
        <div>
            <h1><?= e(t('notifications.title')) ?></h1>
            <p class="muted"><?= e(t('notifications.description')) ?></p>
        </div>
        <form method="post" action="<?= e(url('/notifications/read-all')) ?>">
            <?= csrf_field() ?>
            <button class="button secondary" type="submit"><?= e(t('notifications.mark_all_read')) ?></button>
        </form>
    </div>
</section>

<section class="panel">
    <h2><?= e(t('notifications.messages')) ?></h2>
    <?php if (empty($notifications)): ?>
        <p><?= e(t('notifications.empty')) ?></p>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <article class="notification-card type-<?= e($notification['type']) ?> <?= empty($notification['read_at']) ? 'unread' : '' ?>">
                    <div>
                        <strong><?= e($notification['title']) ?></strong>
                        <small><?= e((string) $notification['created_at']) ?> · <?= e($types[$notification['type']] ?? $notification['type']) ?></small>
                    </div>
                    <p><?= nl2br(e($notification['body'])) ?></p>
                    <?php if (empty($notification['read_at'])): ?>
                        <form method="post" action="<?= e(url('/notifications/read')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $notification['recipient_id']) ?>">
                            <button class="button secondary" type="submit"><?= e(t('notifications.mark_read')) ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <details class="notification-settings-panel">
        <summary><?= e(t('notifications.settings')) ?></summary>
        <form method="post" action="<?= e(url('/notifications/preferences')) ?>" class="notification-preferences">
            <?= csrf_field() ?>
            <?php foreach ($channels as $channel): ?>
                <div class="notification-pref-card">
                    <h3><?= e($channel['label']) ?></h3>
                    <?php foreach ($types as $type => $label): ?>
                        <?php $checked = $preferences[$channel['code']][$type] ?? true; ?>
                        <label>
                            <input type="checkbox" name="preferences[<?= e($channel['code']) ?>][<?= e($type) ?>]" value="1" <?= $checked ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <button class="button secondary" type="submit"><?= e(t('notifications.save_settings')) ?></button>
        </form>
    </details>
</section>
