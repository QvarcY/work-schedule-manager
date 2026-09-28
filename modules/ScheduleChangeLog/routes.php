<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\ScheduleChangeLog\Controllers\ScheduleChangeLogController;

$router->get('/schedule-changes', [ScheduleChangeLogController::class, 'index']);
$router->post('/schedule-changes/read', [ScheduleChangeLogController::class, 'markRead']);
$router->get('/schedule-changes/admin', [ScheduleChangeLogController::class, 'adminIndex']);
