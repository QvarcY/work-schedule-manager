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
    'open' => 'Aktīvs',
    'submitted' => 'Gaida apstiprinājumu',
    'approved' => 'Apstiprināts',
    'rejected' => 'Noraidīts',
    'cancelled' => 'Atcelts',
    'expired' => 'Beidzies',
];
?>

<section class="panel">
    <h1>Ielūgumi</h1>
    <p class="muted">Izveido drošu saiti, kuru var nosūtīt jaunam lietotājam. Konts tiek izveidots tikai pēc admina apstiprināšanas.</p>
</section>

<section class="panel">
    <h2>Jauns ielūgums</h2>
    <form method="post" action="<?= e(url('/user-invitations/admin/create')) ?>" class="invite-create-grid">
        <?= csrf_field() ?>
        <input name="invited_contact" type="text" placeholder="E-pasts vai telefons">
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
        <input name="valid_days" type="number" min="1" max="30" value="7" aria-label="Derīgs dienas">
        <label class="checkbox-pill">
            <input type="checkbox" name="can_be_scheduled" value="1" checked>
            <span>Persona grafikam</span>
        </label>
        <button class="button" type="submit">Izveidot saiti</button>
    </form>
</section>

<section class="panel">
    <h2>Ielūgumu saraksts</h2>
    <?php if (empty($invitations)): ?>
        <p>Nav izveidotu ielūgumu.</p>
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
                        <span><strong>Kontaktinformācija:</strong> <?= e($invitation['invited_contact'] ?: '-') ?></span>
                        <span><strong>Izveidots:</strong> <?= e((string) $invitation['created_at']) ?></span>
                        <span><strong>Derīgs līdz:</strong> <?= e((string) $invitation['expires_at']) ?></span>
                        <?php if (!empty($invitation['username'])): ?>
                            <span><strong>Pieteicās:</strong> <?= e(trim((string) $invitation['first_name'] . ' ' . (string) $invitation['last_name'])) ?> (<?= e($invitation['username']) ?>)</span>
                        <?php endif; ?>
                        <?php if ($inviteUrl !== ''): ?>
                            <label class="invite-copy-field">
                                <span>Ielūguma saite</span>
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
                                <span>Persona grafikam</span>
                            </label>
                            <button class="button" type="submit">Apstiprināt</button>
                        </form>
                        <form method="post" action="<?= e(url('/user-invitations/admin/reject')) ?>" class="invite-reject-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $invitation['id']) ?>">
                            <input name="admin_note" type="text" placeholder="Komentārs noraidījumam">
                            <button class="button danger" type="submit">Noraidīt</button>
                        </form>
                    <?php elseif ($status === 'open'): ?>
                        <form method="post" action="<?= e(url('/user-invitations/admin/cancel')) ?>" data-confirm="Atcelt šo ielūgumu?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $invitation['id']) ?>">
                            <button class="link-button" type="submit">Atcelt ielūgumu</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
