<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\Notifications\Controllers\NotificationController;

$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/read', [NotificationController::class, 'markRead']);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
$router->post('/notifications/preferences', [NotificationController::class, 'savePreferences']);
$router->get('/notifications/admin', [NotificationController::class, 'adminIndex']);
$router->post('/notifications/admin/send', [NotificationController::class, 'send']);
