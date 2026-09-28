<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;

final class JournalController
{
    public function index(): void
    {
        auth()->requireAdmin();

        $filters = [
            'actor' => trim((string) ($_GET['actor'] ?? '')),
            'action_group' => trim((string) ($_GET['action_group'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = (int) ($_GET['per_page'] ?? 50);
        $activityLog = new ActivityLog();
        $result = $activityLog->paginated($filters, $page, $perPage);

        view('journal/index', [
            'title' => 'Žurnāls',
            'logs' => $result['logs'],
            'filters' => $filters,
            'actors' => $activityLog->actors(),
            'stats' => $activityLog->stats(),
            'page' => $result['page'],
            'pages' => $result['pages'],
            'perPage' => $result['perPage'],
            'total' => $result['total'],
        ]);
    }

    public function prune(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $period = (string) ($_POST['period'] ?? '');
        $deleted = (new ActivityLog())->deleteOlderThan($period);
        ActivityLogger::log('journal_pruned', 'activity_log', null, 'Dzēsti ieraksti: ' . $deleted . ', periods: ' . $period, $admin);

        Session::flash('success', 'Dzēsti žurnāla ieraksti: ' . $deleted);
        redirect('/journal');
    }
}
