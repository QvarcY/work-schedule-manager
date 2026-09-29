<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('system_status.title')) ?></h1>
    <p class="muted"><?= e(t('system_status.description')) ?></p>
</section>

<section class="journal-stats">
    <div class="stat-card">
        <span><?= e(t('system_status.system')) ?></span>
        <strong><?= e($appVersion) ?></strong>
    </div>
    <div class="stat-card">
        <span>PHP</span>
        <strong><?= e($phpVersion) ?></strong>
    </div>
    <div class="stat-card">
        <span><?= e(t('system_status.modules')) ?></span>
        <strong><?= e((string) count($installedModules)) ?></strong>
    </div>
</section>

<section class="panel">
    <h2><?= e(t('system_status.installed_modules')) ?></h2>
    <?php if (empty($installedModules)): ?>
        <p><?= e(t('system_status.no_modules')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('system_status.table.name')) ?></th>
                        <th><?= e(t('system_status.table.version')) ?></th>
                        <th><?= e(t('system_status.table.status')) ?></th>
                        <th><?= e(t('system_status.table.installed')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installedModules as $module): ?>
                        <tr>
                            <td><?= e($module['title'] ?? $module['name']) ?></td>
                            <td><?= e($module['version'] ?? '') ?></td>
                            <td><span class="journal-action <?= (int) ($module['active'] ?? 1) === 1 ? 'user' : 'admin' ?>"><?= e((int) ($module['active'] ?? 1) === 1 ? t('modules.status.active') : t('modules.status.inactive')) ?></span></td>
                            <td><?= e($module['installed_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2><?= e(t('system_status.checks')) ?></h2>
    <div class="status-check-grid">
        <?php foreach ($checks as $check): ?>
            <div class="status-check <?= $check['ok'] ? 'ok' : 'fail' ?>">
                <span><?= e($check['label']) ?></span>
                <strong><?= e($check['ok'] ? t('system_status.ok') : t('system_status.missing')) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</section>
