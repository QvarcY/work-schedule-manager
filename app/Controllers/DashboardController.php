<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Schedule;
use App\Services\AdminTasks;

final class DashboardController
{
    public function index(): void
    {
        $user = auth()->requireLogin();
        $scheduleModel = new Schedule();

        view('dashboard/index', [
            'title' => t('dashboard.title'),
            'user' => $user,
            'latestSchedules' => $scheduleModel->latest(5),
            'adminTasks' => ($user['role'] ?? '') === 'admin' ? (new AdminTasks())->pending() : [],
        ]);
    }
}
