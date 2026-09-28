<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\UserInvitations\Controllers\UserInvitationController;

$router->get('/user-invitations/admin', [UserInvitationController::class, 'adminIndex']);
$router->post('/user-invitations/admin/create', [UserInvitationController::class, 'create']);
$router->post('/user-invitations/admin/approve', [UserInvitationController::class, 'approve']);
$router->post('/user-invitations/admin/reject', [UserInvitationController::class, 'reject']);
$router->post('/user-invitations/admin/cancel', [UserInvitationController::class, 'cancel']);
$router->get('/invite', [UserInvitationController::class, 'showInvite']);
$router->get('/invite/submitted', [UserInvitationController::class, 'submitted']);
$router->post('/invite', [UserInvitationController::class, 'submitInvite']);
