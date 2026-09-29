<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\EmployeeProfiles\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Services\AccessControl;
use App\Services\ActivityLogger;

final class EmployeeProfileController
{
    public function profile(): void
    {
        $user = auth()->requireLogin();
        (new AccessControl())->ensureSchema();
        $user = (new User())->findById((int) $user['id']) ?: $user;
        ActivityLogger::log('employee_profile_viewed', 'user', (int) $user['id'], 'profile viewed', $user);

        view('employee-profiles/profile', [
            'title' => t('employee_profiles.profile.title'),
            'user' => $user,
            'hoursThisMonth' => $this->hoursThisMonth((int) $user['id']),
        ]);
    }

    public function updateProfile(): void
    {
        $user = auth()->requireLogin();
        (new AccessControl())->ensureSchema();
        $user = (new User())->findById((int) $user['id']) ?: $user;
        Csrf::verify($_POST['_csrf'] ?? null);

        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $receiveAllScheduleUpdates = isset($_POST['receive_all_schedule_updates']) ? 1 : 0;
        $changed = [];

        if ($email !== (string) ($user['email'] ?? '')) {
            $changed[] = 'email';
        }

        if ($phone !== (string) ($user['phone'] ?? '')) {
            $changed[] = 'phone';
        }

        if ($password !== '') {
            $changed[] = 'password';
        }

        if ($receiveAllScheduleUpdates !== (int) ($user['receive_all_schedule_updates'] ?? 0)) {
            $changed[] = 'schedule_notifications';
        }

        $params = [$email ?: null, $phone ?: null, $receiveAllScheduleUpdates];
        if ($password !== '') {
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $params[] = (int) $user['id'];
            $stmt = Database::connection()->prepare(
                'UPDATE users SET email = ?, phone = ?, receive_all_schedule_updates = ?, password_hash = ? WHERE id = ?'
            );
            $stmt->execute($params);
        } else {
            $params[] = (int) $user['id'];
            $stmt = Database::connection()->prepare(
                'UPDATE users SET email = ?, phone = ?, receive_all_schedule_updates = ? WHERE id = ?'
            );
            $stmt->execute($params);
        }

        ActivityLogger::log(
            'employee_profile_updated',
            'user',
            (int) $user['id'],
            'changed=' . ($changed ? implode(',', $changed) : 'none'),
            $user
        );
        Session::flash('success', t('employee_profiles.profile.save_success'));
        redirect('/employee/profile');
    }

    public function adminIndex(): void
    {
        $admin = auth()->requireAdmin();
        (new AccessControl())->ensureSchema();
        ActivityLogger::log('employee_list_viewed', 'user', null, 'employee list viewed', $admin);

        $viewModeSelect = $this->usersColumnExists('schedule_view_mode')
            ? 'schedule_view_mode'
            : "'full' AS schedule_view_mode";
        $employeeWhere = $this->usersColumnExists('can_be_scheduled')
            ? 'can_be_scheduled = 1'
            : "role = 'employee'";

        $users = Database::connection()
            ->query("SELECT id, username, first_name, last_name, email, phone, {$viewModeSelect}, created_at FROM users WHERE {$employeeWhere} ORDER BY first_name ASC, last_name ASC")
            ->fetchAll();

        view('employee-profiles/admin', [
            'title' => t('employee_profiles.admin.title'),
            'employees' => $users,
        ]);
    }

    public function updateVisibility(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $userId = (int) ($_POST['user_id'] ?? 0);
        $mode = (string) ($_POST['schedule_view_mode'] ?? 'full');
        if (!in_array($mode, ['full', 'own', 'day', 'night'], true)) {
            $mode = 'full';
        }

        if (!$this->usersColumnExists('schedule_view_mode')) {
            Session::flash('error', t('employee_profiles.visibility.migration_required'));
            redirect('/employee-profiles/admin');
        }

        $stmt = Database::connection()->prepare('UPDATE users SET schedule_view_mode = ? WHERE id = ?');
        $stmt->execute([$mode, $userId]);

        ActivityLogger::log('employee_schedule_visibility_updated', 'user', $userId, 'visibility=' . $mode, $admin);
        Session::flash('success', t('employee_profiles.visibility.save_success'));
        redirect('/employee-profiles/admin');
    }

    private function hoursThisMonth(int $userId): float
    {
        $columnStmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS column_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $columnStmt->execute(['employees', 'user_id']);
        if ((int) ($columnStmt->fetch()['column_count'] ?? 0) === 0) {
            return 0.0;
        }

        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(e.hours), 0) AS hours
             FROM employees e
             JOIN schedules s ON s.id = e.schedule_id
             WHERE e.user_id = ?
               AND s.created_at >= DATE_FORMAT(CURRENT_DATE, "%Y-%m-01")'
        );
        $stmt->execute([$userId]);

        return (float) ($stmt->fetch()['hours'] ?? 0);
    }

    private function usersColumnExists(string $column): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS column_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute(['users', $column]);

        return (int) ($stmt->fetch()['column_count'] ?? 0) > 0;
    }
}
