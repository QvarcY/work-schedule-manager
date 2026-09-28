<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Sistēmas statuss</h1>
    <p class="muted">Ātrs tehniskais pārskats pēc atjauninājumiem un moduļu uzstādīšanas.</p>
</section>

<section class="journal-stats">
    <div class="stat-card">
        <span>Sistēma</span>
        <strong><?= e($appVersion) ?></strong>
    </div>
    <div class="stat-card">
        <span>PHP</span>
        <strong><?= e($phpVersion) ?></strong>
    </div>
    <div class="stat-card">
        <span>Moduļi</span>
        <strong><?= e((string) count($installedModules)) ?></strong>
    </div>
</section>

<section class="panel">
    <h2>Uzstādītie moduļi</h2>
    <?php if (empty($installedModules)): ?>
        <p>Nav uzstādītu moduļu.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nosaukums</th>
                        <th>Versija</th>
                        <th>Statuss</th>
                        <th>Uzstādīts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installedModules as $module): ?>
                        <tr>
                            <td><?= e($module['title'] ?? $module['name']) ?></td>
                            <td><?= e($module['version'] ?? '') ?></td>
                            <td><span class="journal-action <?= (int) ($module['active'] ?? 1) === 1 ? 'user' : 'admin' ?>"><?= (int) ($module['active'] ?? 1) === 1 ? 'Aktīvs' : 'Neaktīvs' ?></span></td>
                            <td><?= e($module['installed_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Pamatpārbaudes</h2>
    <div class="status-check-grid">
        <?php foreach ($checks as $check): ?>
            <div class="status-check <?= $check['ok'] ? 'ok' : 'fail' ?>">
                <span><?= e($check['label']) ?></span>
                <strong><?= $check['ok'] ? 'OK' : 'Trūkst' ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</section>
