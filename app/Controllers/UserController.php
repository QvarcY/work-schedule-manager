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
use App\Core\Session;
use App\Models\User;
use App\Services\AccessControl;
use App\Services\ActivityLogger;

final class UserController
{
    public function index(): void
    {
        auth()->requireAdmin();
        $access = new AccessControl();

        view('users/index', [
            'title' => 'Lietotāji',
            'users' => (new User())->all(),
            'roles' => $access->roles(),
            'permissions' => $access->permissions(),
            'rolePermissionMap' => $access->rolePermissionMap(),
        ]);
    }

    public function store(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'user');
        $profile = $this->profileFromRequest();

        if ($username === '' || $password === '') {
            Session::flash('error', 'Lietotājvārds un parole ir obligāti.');
            redirect('/users');
        }

        (new User())->create($username, $password, $role, $profile);
        ActivityLogger::log('user_created', 'user', null, $username . ' / ' . $role);
        Session::flash('success', 'Lietotājs pievienots.');
        redirect('/users');
    }

    public function update(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'user');

        (new User())->updateUser(
            $id,
            $username,
            $role,
            (string) ($_POST['password'] ?? ''),
            $this->profileFromRequest()
        );

        ActivityLogger::log('user_updated', 'user', $id, $username . ' / ' . $role);
        Session::flash('success', 'Lietotājs saglabāts.');
        redirect('/users');
    }

    public function delete(): void
    {
        $currentUser = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $currentUser['id']) {
            Session::flash('error', 'Nevar dzēst lietotāju, ar kuru šobrīd esi pieslēdzies.');
            redirect('/users');
        }

        (new User())->delete($id);
        ActivityLogger::log('user_deleted', 'user', $id, 'Lietotājs dzēsts.');
        Session::flash('success', 'Lietotājs dzēsts.');
        redirect('/users');
    }

    public function updateRolePermissions(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $role = (string) ($_POST['role'] ?? '');
        $permissions = is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];

        (new AccessControl())->updateRolePermissions($role, array_map('strval', $permissions));
        ActivityLogger::log('role_permissions_updated', 'role', null, $role);
        Session::flash('success', 'Lomas tiesības saglabātas.');
        redirect('/users');
    }

    private function profileFromRequest(): array
    {
        return [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'can_be_scheduled' => isset($_POST['can_be_scheduled']) ? 1 : 0,
            'schedule_view_mode' => (string) ($_POST['schedule_view_mode'] ?? 'full'),
            'show_hours_summary' => isset($_POST['show_hours_summary']) ? 1 : 0,
        ];
    }
}
