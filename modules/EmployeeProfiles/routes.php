<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\EmployeeProfiles\Controllers\EmployeeProfileController;

$router->get('/employee/profile', [EmployeeProfileController::class, 'profile']);
$router->post('/employee/profile', [EmployeeProfileController::class, 'updateProfile']);
$router->get('/employee-profiles/admin', [EmployeeProfileController::class, 'adminIndex']);
$router->post('/employee-profiles/admin/visibility', [EmployeeProfileController::class, 'updateVisibility']);
