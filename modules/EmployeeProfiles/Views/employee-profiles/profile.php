<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$fullName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
$displayName = $fullName !== '' ? $fullName : (string) ($user['username'] ?? '');
?>

<section class="panel">
    <h1><?= e(t('employee_profiles.profile.title')) ?></h1>
    <p class="profile-name"><strong><?= e($displayName) ?></strong></p>
    <p class="muted"><?= e(t('employee_profiles.profile.month_hours', ['hours' => $hoursThisMonth])) ?></p>
</section>

<section class="panel">
    <h2><?= e(t('employee_profiles.profile.information')) ?></h2>
    <form method="post" action="<?= e(url('/employee/profile')) ?>">
        <?= csrf_field() ?>
        <div class="profile-readonly">
            <div>
                <span class="muted"><?= e(t('users.fields.first_name')) ?></span>
                <strong><?= e($user['first_name'] ?? '') ?></strong>
            </div>
            <div>
                <span class="muted"><?= e(t('users.fields.last_name')) ?></span>
                <strong><?= e($user['last_name'] ?? '') ?></strong>
            </div>
        </div>
        <div class="form-row">
            <label><?= e(t('users.fields.email')) ?></label>
            <input name="email" type="email" value="<?= e($user['email'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label><?= e(t('users.fields.phone')) ?></label>
            <input name="phone" type="text" value="<?= e($user['phone'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label><?= e(t('users.fields.new_password')) ?></label>
            <input name="password" type="password" placeholder="<?= e(t('employee_profiles.profile.password_hint')) ?>">
        </div>
        <div class="setting-row">
            <label>
                <input type="checkbox" name="receive_all_schedule_updates" value="1" <?= (int) ($user['receive_all_schedule_updates'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span><?= e(t('employee_profiles.profile.all_schedule_updates')) ?></span>
            </label>
            <small class="muted"><?= e(t('employee_profiles.profile.notification_hint')) ?></small>
        </div>
        <button class="button" type="submit"><?= e(t('common.save')) ?></button>
    </form>
</section>
