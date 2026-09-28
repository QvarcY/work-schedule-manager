<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\DayOffRequests\Controllers\DayOffRequestController;

$router->get('/day-off-requests', [DayOffRequestController::class, 'index']);
$router->post('/day-off-requests', [DayOffRequestController::class, 'store']);
$router->get('/day-off-requests/admin', [DayOffRequestController::class, 'adminIndex']);
$router->post('/day-off-requests/admin/decide', [DayOffRequestController::class, 'decide']);
