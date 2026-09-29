<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$queryBase = array_filter([
    'actor' => $filters['actor'] ?? '',
    'action_group' => $filters['action_group'] ?? '',
    'date_from' => $filters['date_from'] ?? '',
    'date_to' => $filters['date_to'] ?? '',
    'per_page' => (string) ($perPage ?? 50),
], static fn (string $value): bool => $value !== '');

$actionLabels = [
    'login_success' => t('journal.actions.login_success'),
    'login_failed' => t('journal.actions.login_failed'),
    'logout' => t('journal.actions.logout'),
    'schedule_viewed' => t('journal.actions.schedule_viewed'),
    'schedules_list_viewed' => t('journal.actions.schedules_list_viewed'),
    'schedule_created' => t('journal.actions.schedule_created'),
    'schedule_updated' => t('journal.actions.schedule_updated'),
    'schedule_published' => t('journal.actions.schedule_published'),
    'schedule_deleted' => t('journal.actions.schedule_deleted'),
    'employee_profile_viewed' => t('journal.actions.employee_profile_viewed'),
    'employee_profile_updated' => t('journal.actions.employee_profile_updated'),
    'day_off_requested' => t('journal.actions.day_off_requested'),
    'day_off_approved' => t('journal.actions.day_off_approved'),
    'day_off_rejected' => t('journal.actions.day_off_rejected'),
];

$userActions = [
    'schedule_viewed',
    'schedules_list_viewed',
    'employee_profile_viewed',
    'employee_profile_updated',
    'day_off_requested',
];

function journal_page_url(array $queryBase, int $page): string
{
    $queryBase['page'] = (string) $page;
    return url('/journal?' . http_build_query($queryBase));
}
?>

<section class="panel">
    <div class="page-title-row">
        <div>
            <h1><?= e(t('journal.title')) ?></h1>
            <p class="muted"><?= e(t('journal.description')) ?></p>
        </div>
    </div>
</section>

<section class="journal-stats">
    <div class="stat-card">
        <span><?= e(t('journal.stats.total')) ?></span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span><?= e(t('journal.stats.last_day')) ?></span>
        <strong><?= e((string) ($stats['last_day'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card user-accent">
        <span><?= e(t('journal.stats.user_actions')) ?></span>
        <strong><?= e((string) ($stats['employee_actions'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span><?= e(t('journal.stats.logins')) ?></span>
        <strong><?= e((string) ($stats['logins'] ?? 0)) ?></strong>
    </div>
</section>

<section class="panel">
    <form method="get" action="<?= e(url('/journal')) ?>" class="journal-filter-grid">
        <div class="form-row">
            <label><?= e(t('journal.filters.user')) ?></label>
            <select name="actor">
                <option value=""><?= e(t('journal.filters.all_users')) ?></option>
                <?php foreach ($actors as $actor): ?>
                    <option value="<?= e($actor['actor_name']) ?>" <?= ($filters['actor'] ?? '') === $actor['actor_name'] ? 'selected' : '' ?>>
                        <?= e($actor['actor_name']) ?> (<?= e((string) $actor['log_count']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label><?= e(t('journal.filters.action_type')) ?></label>
            <select name="action_group">
                <?php foreach (['' => t('journal.filters.all_actions'), 'user' => t('journal.filters.user_actions'), 'admin' => t('journal.filters.admin_actions'), 'login' => t('journal.filters.logins')] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['action_group'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label><?= e(t('journal.filters.from')) ?></label>
            <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label><?= e(t('journal.filters.to')) ?></label>
            <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label><?= e(t('journal.filters.per_page')) ?></label>
            <select name="per_page">
                <?php foreach ([25, 50, 100, 200] as $option): ?>
                    <option value="<?= $option ?>" <?= (int) $perPage === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="journal-filter-actions">
            <button class="button" type="submit"><?= e(t('common.filter')) ?></button>
            <a class="button secondary" href="<?= e(url('/journal')) ?>"><?= e(t('common.clear')) ?></a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="page-title-row">
        <div>
            <h2><?= e(t('journal.entries')) ?></h2>
            <p class="muted"><?= e(t('journal.result_summary', ['total' => $total, 'page' => $page, 'pages' => $pages])) ?></p>
        </div>
        <form method="post" action="<?= e(url('/journal/prune')) ?>" class="compact-form" data-confirm="<?= e(t('journal.prune.confirm')) ?>">
            <?= csrf_field() ?>
            <select name="period">
                <option value="30d"><?= e(t('journal.prune.30d')) ?></option>
                <option value="90d"><?= e(t('journal.prune.90d')) ?></option>
                <option value="180d"><?= e(t('journal.prune.180d')) ?></option>
                <option value="365d"><?= e(t('journal.prune.365d')) ?></option>
            </select>
            <button class="button danger" type="submit"><?= e(t('journal.prune.submit')) ?></button>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <p><?= e(t('journal.empty')) ?></p>
    <?php else: ?>
        <div class="table-wrap journal-table-wrap">
            <table class="journal-table">
                <thead>
                    <tr>
                        <th><?= e(t('journal.table.time')) ?></th>
                        <th><?= e(t('journal.table.user')) ?></th>
                        <th><?= e(t('journal.table.action')) ?></th>
                        <th><?= e(t('journal.table.object')) ?></th>
                        <th><?= e(t('journal.table.description')) ?></th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $isUserAction = in_array($log['action'], $userActions, true);
                            $actionLabel = $actionLabels[$log['action']] ?? $log['action'];
                        ?>
                        <tr class="<?= $isUserAction ? 'journal-user-row' : 'journal-admin-row' ?>">
                            <td><?= e((string) $log['created_at']) ?></td>
                            <td><strong><?= e($log['actor_name'] ?: '-') ?></strong></td>
                            <td><span class="journal-action <?= $isUserAction ? 'user' : 'admin' ?>"><?= e($actionLabel) ?></span></td>
                            <td><?= e(trim(($log['entity_type'] ?? '') . ' #' . ($log['entity_id'] ?? ''), ' #')) ?></td>
                            <td><?= e($log['message'] ?: '') ?></td>
                            <td><?= e($log['ip_address'] ?: '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination">
            <?php if ($page > 1): ?>
                <a class="button secondary" href="<?= e(journal_page_url($queryBase, $page - 1)) ?>"><?= e(t('common.previous')) ?></a>
            <?php endif; ?>
            <span><?= e((string) $page) ?> / <?= e((string) $pages) ?></span>
            <?php if ($page < $pages): ?>
                <a class="button secondary" href="<?= e(journal_page_url($queryBase, $page + 1)) ?>"><?= e(t('common.next')) ?></a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
