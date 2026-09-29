<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$roleLabels = [];
foreach ($roles as $role) {
    $roleLabels[(string) $role['code']] = (string) $role['label'];
}

$statusLabels = [
    'open' => t('invitations.status.open'),
    'submitted' => t('invitations.status.submitted'),
    'approved' => t('invitations.status.approved'),
    'rejected' => t('invitations.status.rejected'),
    'cancelled' => t('invitations.status.cancelled'),
    'expired' => t('invitations.status.expired'),
];
?>

<section class="panel">
    <h1><?= e(t('invitations.admin.title')) ?></h1>
    <p class="muted"><?= e(t('invitations.admin.description')) ?></p>
</section>

<section class="panel">
    <h2><?= e(t('invitations.create.title')) ?></h2>
    <form method="post" action="<?= e(url('/user-invitations/admin/create')) ?>" class="invite-create-grid">
        <?= csrf_field() ?>
        <input name="invited_contact" type="text" placeholder="<?= e(t('invitations.fields.contact')) ?>">
        <select name="role">
            <?php foreach ($roleLabels as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= $code === 'employee' ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="schedule_view_mode">
            <?php foreach ($viewModes as $mode => $label): ?>
                <option value="<?= e($mode) ?>" <?= $mode === 'own' ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="valid_days" type="number" min="1" max="30" value="7" aria-label="<?= e(t('invitations.fields.valid_days')) ?>">
        <label class="checkbox-pill">
            <input type="checkbox" name="can_be_scheduled" value="1" checked>
            <span><?= e(t('users.fields.schedulable')) ?></span>
        </label>
        <button class="button" type="submit"><?= e(t('invitations.create.submit')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('invitations.list')) ?></h2>
    <?php if (empty($invitations)): ?>
        <p><?= e(t('invitations.empty')) ?></p>
    <?php else: ?>
        <div class="invite-list">
            <?php foreach ($invitations as $invitation): ?>
                <?php
                    $status = (string) ($invitation['status'] ?? 'open');
                    $inviteUrl = $status === 'open' && !empty($invitation['token'])
                        ? url('/invite?token=' . $invitation['token'])
                        : '';
                ?>
                <article class="invite-card status-<?= e($status) ?>">
                    <div class="invite-card-head">
                        <div>
                            <strong><?= e($statusLabels[$status] ?? $status) ?></strong>
                            <small><?= e($roleLabels[$invitation['intended_role']] ?? $invitation['intended_role']) ?> · <?= e($viewModes[$invitation['schedule_view_mode']] ?? $invitation['schedule_view_mode']) ?></small>
                        </div>
                        <span class="role-badge"><?= e((string) ($invitation['id'] ?? '')) ?></span>
                    </div>

                    <div class="invite-details">
                        <span><strong><?= e(t('invitations.fields.contact')) ?>:</strong> <?= e($invitation['invited_contact'] ?: '-') ?></span>
                        <span><strong><?= e(t('invitations.fields.created')) ?>:</strong> <?= e((string) $invitation['created_at']) ?></span>
                        <span><strong><?= e(t('invitations.fields.expires')) ?>:</strong> <?= e((string) $invitation['expires_at']) ?></span>
                        <?php if (!empty($invitation['username'])): ?>
                            <span><strong><?= e(t('invitations.fields.applicant')) ?>:</strong> <?= e(trim((string) $invitation['first_name'] . ' ' . (string) $invitation['last_name'])) ?> (<?= e($invitation['username']) ?>)</span>
                        <?php endif; ?>
                        <?php if ($inviteUrl !== ''): ?>
                            <label class="invite-copy-field">
                                <span><?= e(t('invitations.fields.link')) ?></span>
                                <input type="text" value="<?= e($inviteUrl) ?>" readonly onclick="this.select()">
                            </label>
                        <?php endif; ?>
                    </div>

                    <?php if ($status === 'submitted'): ?>
                        <form method="post" action="<?= e(url('/user-invitations/admin/approve')) ?>" class="invite-decision-grid">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $invitation['id']) ?>">
                            <select name="role">
                                <?php foreach ($roleLabels as $code => $label): ?>
                                    <option value="<?= e($code) ?>" <?= $invitation['intended_role'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="schedule_view_mode">
                                <?php foreach ($viewModes as $mode => $label): ?>
                                    <option value="<?= e($mode) ?>" <?= $invitation['schedule_view_mode'] === $mode ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="checkbox-pill">
                                <input type="checkbox" name="can_be_scheduled" value="1" <?= (int) $invitation['can_be_scheduled'] === 1 ? 'checked' : '' ?>>
                                <span><?= e(t('users.fields.schedulable')) ?></span>
                            </label>
                            <button class="button" type="submit"><?= e(t('common.approve')) ?></button>
                        </form>
                        <form method="post" action="<?= e(url('/user-invitations/admin/reject')) ?>" class="invite-reject-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $invitation['id']) ?>">
                            <input name="admin_note" type="text" placeholder="<?= e(t('invitations.reject.comment')) ?>">
                            <button class="button danger" type="submit"><?= e(t('common.reject')) ?></button>
                        </form>
                    <?php elseif ($status === 'open'): ?>
                        <form method="post" action="<?= e(url('/user-invitations/admin/cancel')) ?>" data-confirm="<?= e(t('invitations.cancel.confirm')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $invitation['id']) ?>">
                            <button class="link-button" type="submit"><?= e(t('invitations.cancel.submit')) ?></button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
