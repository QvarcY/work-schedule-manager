<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Paziņojumu pārvaldība</h1>
    <p class="muted">Nosūti īsu ziņu sistēmas lietotājiem. Aktīvie kanāli tiek pārvaldīti paziņojumu moduļos un lietotāju preferencēs.</p>
</section>

<section class="panel">
    <h2>Jauns paziņojums</h2>
    <form method="post" action="<?= e(url('/notifications/admin/send')) ?>" class="notification-send-form">
        <?= csrf_field() ?>
        <input name="title" type="text" placeholder="Virsraksts" required>
        <select name="type">
            <?php foreach ($types as $type => $label): ?>
                <option value="<?= e($type) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="target" data-notification-target>
            <option value="all">Visiem</option>
            <option value="role">Konkrētai lomai</option>
            <option value="user">Konkrētam lietotājam</option>
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
        <textarea name="body" rows="4" placeholder="Paziņojuma teksts" required></textarea>
        <button class="button" type="submit">Nosūtīt</button>
    </form>
</section>

<section class="panel">
    <h2>Pēdējie paziņojumi</h2>
    <?php if (empty($recent)): ?>
        <p>Vēl nav nosūtītu paziņojumu.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Virsraksts</th>
                        <th>Tips</th>
                        <th>Izveidots</th>
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
