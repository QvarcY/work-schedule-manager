<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$invitedContact = trim((string) ($invitation['invited_contact'] ?? ''));
$prefillEmail = filter_var($invitedContact, FILTER_VALIDATE_EMAIL) ? $invitedContact : '';
$prefillPhone = $prefillEmail === '' ? $invitedContact : '';
?>

<section class="panel invite-accept-panel">
    <h1><?= e(t('invitations.accept.title')) ?></h1>

    <?php if (!$isAvailable): ?>
        <p><?= e(t('invitations.validation.unavailable')) ?></p>
        <a class="button secondary" href="<?= e(url('/login')) ?>"><?= e(t('invitations.back_to_login')) ?></a>
    <?php else: ?>
        <form method="post" action="<?= e(url('/invite')) ?>" data-password-meter>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="grid">
                <div class="form-row">
                    <label><?= e(t('users.fields.first_name')) ?></label>
                    <input name="first_name" type="text" required>
                </div>
                <div class="form-row">
                    <label><?= e(t('users.fields.last_name')) ?></label>
                    <input name="last_name" type="text" required>
                </div>
            </div>
            <div class="grid">
                <div class="form-row">
                    <label><?= e(t('users.fields.email')) ?></label>
                    <input name="email" type="email" value="<?= e($prefillEmail) ?>" autocomplete="email" required>
                </div>
                <div class="form-row">
                    <label><?= e(t('users.fields.phone')) ?></label>
                    <input name="phone" type="text" value="<?= e($prefillPhone) ?>" autocomplete="tel">
                </div>
            </div>
            <div class="form-row">
                <label><?= e(t('users.fields.password')) ?></label>
                <input name="password" type="password" autocomplete="new-password" required data-password-input>
                <div class="password-meter" aria-hidden="true">
                    <span data-password-bar></span>
                </div>
                <small class="muted" data-password-label><?= e(t('invitations.password.strength')) ?></small>
            </div>
            <button class="button" type="submit"><?= e(t('invitations.accept.submit')) ?></button>
        </form>
    <?php endif; ?>
</section>

<script>
document.querySelectorAll('[data-password-meter]').forEach(function (form) {
    var input = form.querySelector('[data-password-input]');
    var bar = form.querySelector('[data-password-bar]');
    var label = form.querySelector('[data-password-label]');
    if (!input || !bar || !label) return;

    function score(value) {
        var points = 0;
        if (value.length >= 10) points++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) points++;
        if (/\d/.test(value)) points++;
        if (/[^A-Za-z0-9]/.test(value)) points++;
        return points;
    }

    input.addEventListener('input', function () {
        var points = score(input.value);
        var labels = <?= json_encode([
            t('invitations.password.weak'),
            t('invitations.password.weak'),
            t('invitations.password.medium'),
            t('invitations.password.good'),
            t('invitations.password.strong'),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        bar.style.width = (points * 25) + '%';
        bar.dataset.score = String(points);
        label.textContent = labels[points];
    });
});
</script>
