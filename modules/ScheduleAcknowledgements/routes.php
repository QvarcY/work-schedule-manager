<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\ScheduleAcknowledgements\Controllers\ScheduleAcknowledgementController;

$router->get('/schedule-acknowledgements', [ScheduleAcknowledgementController::class, 'index']);
$router->post('/schedule-acknowledgements/ack', [ScheduleAcknowledgementController::class, 'acknowledge']);
$router->get('/schedule-acknowledgements/admin', [ScheduleAcknowledgementController::class, 'adminIndex']);
