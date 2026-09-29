<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use App\Core\App;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ModuleController;
use App\Controllers\JournalController;
use App\Controllers\LocaleController;
use App\Controllers\ScheduleController;
use App\Controllers\ShiftTypeController;
use App\Controllers\UserController;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/app/bootstrap.php';

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->get('/about', [AuthController::class, 'about']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/locale', [LocaleController::class, 'update']);

$router->get('/schedules', [ScheduleController::class, 'index']);
$router->get('/schedules/show', [ScheduleController::class, 'show']);
$router->get('/schedules/create', [ScheduleController::class, 'create']);
$router->post('/schedules/store', [ScheduleController::class, 'store']);
$router->get('/schedules/edit', [ScheduleController::class, 'edit']);
$router->post('/schedules/update', [ScheduleController::class, 'update']);
$router->post('/schedules/delete', [ScheduleController::class, 'delete']);
$router->post('/schedules/publish', [ScheduleController::class, 'publish']);

$router->get('/shift-types', [ShiftTypeController::class, 'index']);
$router->post('/shift-types/store', [ShiftTypeController::class, 'store']);
$router->post('/shift-types/update', [ShiftTypeController::class, 'update']);
$router->post('/shift-types/delete', [ShiftTypeController::class, 'delete']);

$router->get('/users', [UserController::class, 'index']);
$router->post('/users/store', [UserController::class, 'store']);
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/delete', [UserController::class, 'delete']);
$router->post('/roles/permissions', [UserController::class, 'updateRolePermissions']);


$router->get('/modules', [ModuleController::class, 'index']);
$router->post('/modules/install', [ModuleController::class, 'install']);
$router->post('/modules/upload', [ModuleController::class, 'upload']);
$router->post('/modules/activate', [ModuleController::class, 'activate']);
$router->post('/modules/deactivate', [ModuleController::class, 'deactivate']);
$router->post('/modules/uninstall', [ModuleController::class, 'uninstall']);

$router->get('/journal', [JournalController::class, 'index']);
$router->post('/journal/prune', [JournalController::class, 'prune']);

if (class_exists(\App\Services\ModuleManager::class)) {
    (new \App\Services\ModuleManager())->registerInstalledRoutes($router);
}

App::run($router);
