<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\EmailNotifications\Controllers\EmailNotificationController;

$router->get('/email-notifications', [EmailNotificationController::class, 'index']);
$router->post('/email-notifications/settings', [EmailNotificationController::class, 'saveSettings']);
$router->post('/email-notifications/test', [EmailNotificationController::class, 'sendTest']);
