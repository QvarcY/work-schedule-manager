<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>E-pasta paziņojumi</h1>
    <p class="muted">Šis modulis pieslēdz e-pastu esošajai paziņojumu sistēmai. Lietotāji var savās paziņojumu preferencēs izvēlēties, kurus paziņojumu tipus saņemt e-pastā.</p>
</section>

<section class="panel">
    <h2>Iestatījumi</h2>
    <form method="post" action="<?= e(url('/email-notifications/settings')) ?>" class="user-create-grid">
        <?= csrf_field() ?>
        <label class="checkbox-pill">
            <input type="checkbox" name="enabled" value="1" <?= ($settings['enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            <span>Ieslēgt e-pasta sūtīšanu</span>
        </label>
        <input name="from_email" type="email" value="<?= e($settings['from_email'] ?? '') ?>" placeholder="Sūtītāja e-pasts" required>
        <input name="from_name" type="text" value="<?= e($settings['from_name'] ?? '') ?>" placeholder="Sūtītāja nosaukums">
        <input name="reply_to" type="email" value="<?= e($settings['reply_to'] ?? '') ?>" placeholder="Reply-To e-pasts">
        <input name="subject_prefix" type="text" value="<?= e($settings['subject_prefix'] ?? '') ?>" placeholder="Temata prefikss">
        <button class="button" type="submit">Saglabāt</button>
    </form>
</section>

<section class="panel">
    <h2>Tests</h2>
    <form method="post" action="<?= e(url('/email-notifications/test')) ?>" class="compact-form">
        <?= csrf_field() ?>
        <input name="test_email" type="email" placeholder="Saņēmēja e-pasts" required>
        <button class="button secondary" type="submit">Nosūtīt testa e-pastu</button>
    </form>
</section>

<section class="panel">
    <h2>Pēdējās e-pasta piegādes</h2>
    <?php if (empty($recentDeliveries)): ?>
        <p>Vēl nav e-pasta piegāžu.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Laiks</th>
                        <th>Lietotājs</th>
                        <th>E-pasts</th>
                        <th>Paziņojums</th>
                        <th>Statuss</th>
                        <th>Kļūda</th>
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
