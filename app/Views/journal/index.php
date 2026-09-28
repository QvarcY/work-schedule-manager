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
    'login_success' => 'Pieslēgšanās',
    'login_failed' => 'Neveiksmīga pieslēgšanās',
    'logout' => 'Izgāja',
    'schedule_viewed' => 'Apskatīja grafiku',
    'schedules_list_viewed' => 'Apskatīja grafiku sarakstu',
    'schedule_created' => 'Izveidoja grafiku',
    'schedule_updated' => 'Laboja grafiku',
    'schedule_published' => 'Publicēja grafiku',
    'schedule_deleted' => 'Dzēsa grafiku',
    'employee_profile_viewed' => 'Apskatīja profilu',
    'employee_profile_updated' => 'Laboja profilu',
    'day_off_requested' => 'Pieteica brīvdienu',
    'day_off_approved' => 'Apstiprināja brīvdienu',
    'day_off_rejected' => 'Noraidīja brīvdienu',
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
            <h1>Žurnāls</h1>
            <p class="muted">Filtrē, pārskati un tīri sistēmas darbību vēsturi.</p>
        </div>
    </div>
</section>

<section class="journal-stats">
    <div class="stat-card">
        <span>Kopā</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>Pēdējās 24h</span>
        <strong><?= e((string) ($stats['last_day'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card user-accent">
        <span>Lietotāju darbības</span>
        <strong><?= e((string) ($stats['employee_actions'] ?? 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>Pieslēgšanās</span>
        <strong><?= e((string) ($stats['logins'] ?? 0)) ?></strong>
    </div>
</section>

<section class="panel">
    <form method="get" action="<?= e(url('/journal')) ?>" class="journal-filter-grid">
        <div class="form-row">
            <label>Lietotājs</label>
            <select name="actor">
                <option value="">Visi lietotāji</option>
                <?php foreach ($actors as $actor): ?>
                    <option value="<?= e($actor['actor_name']) ?>" <?= ($filters['actor'] ?? '') === $actor['actor_name'] ? 'selected' : '' ?>>
                        <?= e($actor['actor_name']) ?> (<?= e((string) $actor['log_count']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label>Darbības tips</label>
            <select name="action_group">
                <?php foreach (['' => 'Visas darbības', 'user' => 'Lietotāju darbības', 'admin' => 'Admin/core darbības', 'login' => 'Pieslēgšanās'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['action_group'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label>No</label>
            <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label>Līdz</label>
            <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label>Ieraksti lapā</label>
            <select name="per_page">
                <?php foreach ([25, 50, 100, 200] as $option): ?>
                    <option value="<?= $option ?>" <?= (int) $perPage === $option ? 'selected' : '' ?>><?= $option ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="journal-filter-actions">
            <button class="button" type="submit">Filtrēt</button>
            <a class="button secondary" href="<?= e(url('/journal')) ?>">Notīrīt</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="page-title-row">
        <div>
            <h2>Ieraksti</h2>
            <p class="muted">Atrasti <?= e((string) $total) ?> ieraksti. Lapa <?= e((string) $page) ?> no <?= e((string) $pages) ?>.</p>
        </div>
        <form method="post" action="<?= e(url('/journal/prune')) ?>" class="compact-form" data-confirm="Dzēst vecākos žurnāla ierakstus? Šo darbību nevarēs atsaukt.">
            <?= csrf_field() ?>
            <select name="period">
                <option value="30d">Vecāki par 30 dienām</option>
                <option value="90d">Vecāki par 90 dienām</option>
                <option value="180d">Vecāki par 180 dienām</option>
                <option value="365d">Vecāki par 1 gadu</option>
            </select>
            <button class="button danger" type="submit">Dzēst vecos</button>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <p>Žurnālā nav ierakstu šiem filtriem.</p>
    <?php else: ?>
        <div class="table-wrap journal-table-wrap">
            <table class="journal-table">
                <thead>
                    <tr>
                        <th>Laiks</th>
                        <th>Lietotājs</th>
                        <th>Darbība</th>
                        <th>Objekts</th>
                        <th>Apraksts</th>
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
                <a class="button secondary" href="<?= e(journal_page_url($queryBase, $page - 1)) ?>">Iepriekšējā</a>
            <?php endif; ?>
            <span><?= e((string) $page) ?> / <?= e((string) $pages) ?></span>
            <?php if ($page < $pages): ?>
                <a class="button secondary" href="<?= e(journal_page_url($queryBase, $page + 1)) ?>">Nākamā</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
