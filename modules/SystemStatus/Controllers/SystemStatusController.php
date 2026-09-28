<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\SystemStatus\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Services\ModuleManager;

final class SystemStatusController
{
    public function index(): void
    {
        auth()->requireAdmin();

        $manager = new ModuleManager();

        view('system-status/index', [
            'title' => 'Sistēmas statuss',
            'phpVersion' => PHP_VERSION,
            'appVersion' => (string) Env::get('APP_VERSION', '1.0.0'),
            'installedModules' => $this->safeInstalledModules($manager),
            'checks' => $this->checks(),
        ]);
    }

    private function safeInstalledModules(ModuleManager $manager): array
    {
        try {
            return $manager->installedModules();
        } catch (\Throwable) {
            return [];
        }
    }

    private function checks(): array
    {
        $checks = [];
        foreach ([
            'users',
            'schedules',
            'employees',
            'shifts',
            'shift_types',
            'schedule_day_settings',
            'modules',
            'activity_logs',
            'day_off_requests',
            'schedule_acknowledgements',
        ] as $table) {
            $checks[] = [
                'label' => 'Tabula: ' . $table,
                'ok' => $this->tableExists($table),
            ];
        }

        foreach ([
            ['employees', 'user_id'],
            ['users', 'first_name'],
            ['users', 'last_name'],
            ['users', 'phone'],
            ['day_off_requests', 'employee_seen_at'],
        ] as [$table, $column]) {
            $checks[] = [
                'label' => 'Kolonna: ' . $table . '.' . $column,
                'ok' => $this->columnExists($table, $column),
            ];
        }

        return $checks;
    }

    private function tableExists(string $table): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS item_count
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);

        return (int) ($stmt->fetch()['item_count'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS item_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) ($stmt->fetch()['item_count'] ?? 0) > 0;
    }
}
