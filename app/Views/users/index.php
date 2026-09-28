<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$roleOptions = [];
foreach ($roles as $role) {
    $roleOptions[(string) $role['code']] = (string) $role['label'];
}

$scheduleViewOptions = [
    'full' => 'Pilnu grafiku',
    'own' => 'Tikai savas maiņas',
    'day' => 'Tikai dienas maiņas',
    'night' => 'Tikai nakts maiņas',
];
?>

<section class="panel">
    <h1>Lietotāji</h1>
    <p class="muted">Visi konti, kas var pieslēgties sistēmai. Ar atzīmi “persona grafikam” kontu var izmantot arī grafika darbinieku sarakstā.</p>
</section>

<section class="panel">
    <h2>Jauns lietotājs</h2>
    <form method="post" action="<?= e(url('/users/store')) ?>" class="user-create-grid">
        <?= csrf_field() ?>
        <input name="username" type="text" placeholder="Lietotājvārds" autocomplete="username" required>
        <input name="password" type="password" placeholder="Parole" required>
        <select name="role">
            <?php foreach ($roleOptions as $code => $label): ?>
                <option value="<?= e($code) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="first_name" type="text" placeholder="Vārds">
        <input name="last_name" type="text" placeholder="Uzvārds">
        <input name="email" type="email" placeholder="E-pasts">
        <input name="phone" type="text" placeholder="Telefons">
        <select name="schedule_view_mode">
            <?php foreach ($scheduleViewOptions as $mode => $label): ?>
                <option value="<?= e($mode) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="checkbox-pill">
            <input type="checkbox" name="can_be_scheduled" value="1">
            <span>Persona grafikam</span>
        </label>
        <label class="checkbox-pill">
            <input type="checkbox" name="show_hours_summary" value="1">
            <span>Rādīt stundu kopsavilkumu</span>
        </label>
        <button class="button" type="submit">Pievienot</button>
    </form>
</section>

<section class="panel">
    <h2>Esošie lietotāji</h2>
    <div class="user-list">
        <?php foreach ($users as $user): ?>
            <details class="user-list-item">
                <summary>
                    <span>
                        <strong><?= e($user['username']) ?></strong>
                        <small>
                            <?= e(trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')) ?: 'Bez profila vārda') ?>
                        </small>
                    </span>
                    <span class="role-badge"><?= e($roleOptions[$user['role']] ?? $user['role']) ?></span>
                    <?php if ((int) ($user['can_be_scheduled'] ?? 0) === 1): ?>
                        <span class="role-badge soft">Persona grafikam</span>
                    <?php endif; ?>
                    <span class="button secondary user-edit-button">Labot</span>
                </summary>

                <div class="user-edit-panel">
                    <form method="post" action="<?= e(url('/users/update')) ?>" class="user-edit-grid">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">
                        <input name="username" type="text" value="<?= e($user['username']) ?>" autocomplete="username" required>
                        <select name="role">
                            <?php foreach ($roleOptions as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= $user['role'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="first_name" type="text" value="<?= e($user['first_name'] ?? '') ?>" placeholder="Vārds">
                        <input name="last_name" type="text" value="<?= e($user['last_name'] ?? '') ?>" placeholder="Uzvārds">
                        <input name="email" type="email" value="<?= e($user['email'] ?? '') ?>" placeholder="E-pasts">
                        <input name="phone" type="text" value="<?= e($user['phone'] ?? '') ?>" placeholder="Telefons">
                        <select name="schedule_view_mode">
                            <?php foreach ($scheduleViewOptions as $mode => $label): ?>
                                <option value="<?= e($mode) ?>" <?= ($user['schedule_view_mode'] ?? 'full') === $mode ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="password" type="password" placeholder="Jauna parole">
                        <label class="checkbox-pill">
                            <input type="checkbox" name="can_be_scheduled" value="1" <?= (int) ($user['can_be_scheduled'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <span>Persona grafikam</span>
                        </label>
                        <label class="checkbox-pill">
                            <input type="checkbox" name="show_hours_summary" value="1" <?= (int) ($user['show_hours_summary'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <span>Rādīt stundu kopsavilkumu</span>
                        </label>
                        <div class="user-edit-actions">
                            <button class="button secondary" type="submit">Saglabāt</button>
                        </div>
                    </form>
                    <form method="post" action="<?= e(url('/users/delete')) ?>" data-confirm="Dzēst šo lietotāju?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">
                        <button class="link-button" type="submit">Dzēst</button>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <h2>Lomas un tiesības</h2>
    <p class="muted">Šis ir pamata tiesību slānis nākotnei. Esošās admin darbības vēl izmanto admin aizsardzību, bet šeit jau var sagatavot lomu iespējas moduļiem un nākamajiem skatiem.</p>
    <div class="role-permissions-grid">
        <?php foreach ($roles as $role): ?>
            <?php $roleCode = (string) $role['code']; ?>
            <form method="post" action="<?= e(url('/roles/permissions')) ?>" class="role-card">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="<?= e($roleCode) ?>">
                <h3><?= e($role['label']) ?></h3>
                <div class="permission-list">
                    <?php foreach ($permissions as $permission): ?>
                        <?php $permissionCode = (string) $permission['code']; ?>
                        <label>
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="<?= e($permissionCode) ?>"
                                <?= !empty($rolePermissionMap[$roleCode][$permissionCode]) ? 'checked' : '' ?>
                            >
                            <span><?= e($permission['label']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="button secondary" type="submit">Saglabāt tiesības</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>
