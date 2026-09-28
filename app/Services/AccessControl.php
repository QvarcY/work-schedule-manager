<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class AccessControl
{
    private const ROLES = [
        'admin' => 'Admins',
        'moderator' => 'Moderators',
        'control' => 'Kontrole',
        'employee' => 'Darbinieks',
        'viewer' => 'Skatītājs',
        'user' => 'Lietotājs',
    ];

    private const PERMISSIONS = [
        'schedules.view' => 'Skatīt grafikus',
        'schedules.view_full' => 'Skatīt pilnu grafiku',
        'schedules.create' => 'Izveidot grafiku',
        'schedules.edit' => 'Labot grafiku',
        'schedules.delete' => 'Dzēst grafiku',
        'schedules.publish' => 'Publicēt grafiku',
        'shift_types.manage' => 'Pārvaldīt apzīmējumus',
        'users.manage' => 'Pārvaldīt lietotājus',
        'employees.manage' => 'Pārvaldīt darbiniekus',
        'day_off.manage' => 'Apstiprināt brīvdienu pieteikumus',
        'journal.view' => 'Skatīt žurnālu',
        'modules.manage' => 'Pārvaldīt moduļus',
    ];

    private const DEFAULT_ROLE_PERMISSIONS = [
        'admin' => [
            'schedules.view',
            'schedules.view_full',
            'schedules.create',
            'schedules.edit',
            'schedules.delete',
            'schedules.publish',
            'shift_types.manage',
            'users.manage',
            'employees.manage',
            'day_off.manage',
            'journal.view',
            'modules.manage',
        ],
        'moderator' => [
            'schedules.view',
            'schedules.view_full',
            'schedules.create',
            'schedules.edit',
            'schedules.publish',
            'shift_types.manage',
            'employees.manage',
            'day_off.manage',
            'journal.view',
        ],
        'control' => [
            'schedules.view',
            'schedules.view_full',
            'journal.view',
        ],
        'employee' => [
            'schedules.view',
        ],
        'viewer' => [
            'schedules.view',
        ],
        'user' => [
            'schedules.view',
        ],
    ];

    public function ensureSchema(): void
    {
        $db = $this->db();

        $db->exec(
            "CREATE TABLE IF NOT EXISTS roles (
                code VARCHAR(50) PRIMARY KEY,
                label VARCHAR(100) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS permissions (
                code VARCHAR(100) PRIMARY KEY,
                label VARCHAR(160) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec(
            "CREATE TABLE IF NOT EXISTS role_permissions (
                role_code VARCHAR(50) NOT NULL,
                permission_code VARCHAR(100) NOT NULL,
                PRIMARY KEY (role_code, permission_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $addedScheduleFlag = $this->ensureColumn('users', 'can_be_scheduled', "ALTER TABLE users ADD COLUMN can_be_scheduled TINYINT(1) NOT NULL DEFAULT 0 AFTER role");
        $this->ensureColumn('users', 'schedule_view_mode', "ALTER TABLE users ADD COLUMN schedule_view_mode ENUM('full', 'own', 'day', 'night') NOT NULL DEFAULT 'full' AFTER can_be_scheduled");
        $this->ensureColumn('users', 'show_hours_summary', "ALTER TABLE users ADD COLUMN show_hours_summary TINYINT(1) NOT NULL DEFAULT 0 AFTER schedule_view_mode");
        $this->ensureColumn('users', 'receive_all_schedule_updates', "ALTER TABLE users ADD COLUMN receive_all_schedule_updates TINYINT(1) NOT NULL DEFAULT 0 AFTER show_hours_summary");

        if ($addedScheduleFlag) {
            $db->exec("UPDATE users SET can_be_scheduled = 1 WHERE role = 'employee'");
        }

        try {
            $db->exec("ALTER TABLE users MODIFY role ENUM('admin', 'moderator', 'control', 'employee', 'viewer', 'user') NOT NULL DEFAULT 'user'");
        } catch (\Throwable) {
            // Existing installs may have compatible VARCHAR/ENUM variants. Role checks still use the text value.
        }

        $roleStmt = $db->prepare('INSERT INTO roles (code, label, sort_order) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label), sort_order = VALUES(sort_order)');
        $order = 10;
        foreach (self::ROLES as $code => $label) {
            $roleStmt->execute([$code, $label, $order]);
            $order += 10;
        }

        $permissionStmt = $db->prepare('INSERT INTO permissions (code, label) VALUES (?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)');
        foreach (self::PERMISSIONS as $code => $label) {
            $permissionStmt->execute([$code, $label]);
        }

        $permissionCount = (int) ($db->query('SELECT COUNT(*) AS count FROM role_permissions')->fetch()['count'] ?? 0);
        if ($permissionCount === 0) {
            $assignStmt = $db->prepare('INSERT IGNORE INTO role_permissions (role_code, permission_code) VALUES (?, ?)');
            foreach (self::DEFAULT_ROLE_PERMISSIONS as $role => $permissions) {
                foreach ($permissions as $permission) {
                    $assignStmt->execute([$role, $permission]);
                }
            }
        }
    }

    public function roles(): array
    {
        $this->ensureSchema();

        return $this->db()->query('SELECT * FROM roles ORDER BY sort_order ASC, label ASC')->fetchAll();
    }

    public function permissions(): array
    {
        $this->ensureSchema();

        return $this->db()->query('SELECT * FROM permissions ORDER BY label ASC')->fetchAll();
    }

    public function rolePermissionMap(): array
    {
        $this->ensureSchema();
        $rows = $this->db()->query('SELECT role_code, permission_code FROM role_permissions')->fetchAll();
        $map = [];

        foreach ($rows as $row) {
            $map[(string) $row['role_code']][(string) $row['permission_code']] = true;
        }

        return $map;
    }

    public function roleCodes(): array
    {
        return array_keys(self::ROLES);
    }

    public function updateRolePermissions(string $role, array $permissions): void
    {
        $this->ensureSchema();
        if (!in_array($role, $this->roleCodes(), true)) {
            return;
        }

        $allowedPermissions = array_fill_keys(array_keys(self::PERMISSIONS), true);
        $permissions = array_values(array_filter($permissions, static fn ($permission): bool => isset($allowedPermissions[$permission])));

        $db = $this->db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('DELETE FROM role_permissions WHERE role_code = ?');
            $stmt->execute([$role]);

            $stmt = $db->prepare('INSERT INTO role_permissions (role_code, permission_code) VALUES (?, ?)');
            foreach ($permissions as $permission) {
                $stmt->execute([$role, $permission]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function userCan(array $user, string $permission): bool
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        $this->ensureSchema();
        $stmt = $this->db()->prepare('SELECT COUNT(*) AS count FROM role_permissions WHERE role_code = ? AND permission_code = ?');
        $stmt->execute([(string) ($user['role'] ?? 'user'), $permission]);

        return (int) ($stmt->fetch()['count'] ?? 0) > 0;
    }

    private function ensureColumn(string $table, string $column, string $sql): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) AS column_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        if ((int) ($stmt->fetch()['column_count'] ?? 0) === 0) {
            $this->db()->exec($sql);
            return true;
        }

        return false;
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
