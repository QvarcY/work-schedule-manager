<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\SystemStatus\Controllers\SystemStatusController;

$router->get('/system-status', [SystemStatusController::class, 'index']);
