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
    <h1>Mans profils</h1>
    <p class="profile-name"><strong><?= e($displayName) ?></strong></p>
    <p class="muted">Tekosa menesa stundas: <strong><?= e((string) $hoursThisMonth) ?> h</strong></p>
</section>

<section class="panel">
    <h2>Profila informacija</h2>
    <form method="post" action="<?= e(url('/employee/profile')) ?>">
        <?= csrf_field() ?>
        <div class="profile-readonly">
            <div>
                <span class="muted">Vards</span>
                <strong><?= e($user['first_name'] ?? '') ?></strong>
            </div>
            <div>
                <span class="muted">Uzvards</span>
                <strong><?= e($user['last_name'] ?? '') ?></strong>
            </div>
        </div>
        <div class="form-row">
            <label>E-pasts</label>
            <input name="email" type="email" value="<?= e($user['email'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label>Telefons</label>
            <input name="phone" type="text" value="<?= e($user['phone'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label>Jauna parole</label>
            <input name="password" type="password" placeholder="Atstat tuksu, ja nemaini">
        </div>
        <div class="setting-row">
            <label>
                <input type="checkbox" name="receive_all_schedule_updates" value="1" <?= (int) ($user['receive_all_schedule_updates'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span>Saņemt paziņojumus arī par grafika izmaiņām, kas tieši neattiecas uz manām maiņām</span>
            </label>
            <small class="muted">Pēc noklusējuma saņemsi paziņojumu tikai tad, ja grafikā mainītas tieši tavas maiņas.</small>
        </div>
        <button class="button" type="submit">Saglabat</button>
    </form>
</section>
