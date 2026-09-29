<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Session;
use App\Services\ActivityLogger;
use App\Services\ModuleManager;

final class ModuleController
{
    public function index(): void
    {
        auth()->requireAdmin();

        $manager = new ModuleManager();
        $error = null;
        $availableModules = [];
        $installedModules = [];

        try {
            $availableModules = $manager->availableModules();
            $installedModules = $manager->installedModules();
        } catch (\Throwable) {
            $error = t('modules.load_error');
        }

        view('modules/index', [
            'title' => t('modules.title'),
            'availableModules' => $availableModules,
            'installedModules' => $installedModules,
            'moduleError' => $error,
        ]);
    }

    public function install(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $moduleName = trim((string) ($_POST['module'] ?? ''));
        (new ModuleManager())->install($moduleName);
        ActivityLogger::log('module_installed', 'module', null, $moduleName);

        Session::flash('success', t('modules.install.success'));
        redirect('/modules');
    }

    public function upload(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        try {
            $moduleName = (new ModuleManager())->upload($_FILES['module_zip'] ?? []);
            ActivityLogger::log('module_uploaded', 'module', null, $moduleName);
            Session::flash('success', t('modules.upload.success', ['module' => $moduleName]));
        } catch (\Throwable $exception) {
            Session::flash('error', $exception instanceof HttpException ? $exception->getMessage() : t('modules.upload.failed'));
        }

        redirect('/modules');
    }

    public function activate(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $moduleName = trim((string) ($_POST['module'] ?? ''));
        (new ModuleManager())->setActive($moduleName, true);
        ActivityLogger::log('module_activated', 'module', null, $moduleName);
        Session::flash('success', t('modules.activate.success'));
        redirect('/modules');
    }

    public function deactivate(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $moduleName = trim((string) ($_POST['module'] ?? ''));
        (new ModuleManager())->setActive($moduleName, false);
        ActivityLogger::log('module_deactivated', 'module', null, $moduleName);
        Session::flash('success', t('modules.deactivate.success'));
        redirect('/modules');
    }

    public function uninstall(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $deleteFiles = isset($_POST['delete_files']);
        $moduleName = trim((string) ($_POST['module'] ?? ''));
        (new ModuleManager())->uninstall($moduleName, $deleteFiles);
        ActivityLogger::log('module_uninstalled', 'module', null, $moduleName . ($deleteFiles ? ' / files deleted' : ''));
        Session::flash('success', t('modules.uninstall.success'));
        redirect('/modules');
    }
}
