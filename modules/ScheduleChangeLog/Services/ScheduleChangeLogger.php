<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\ScheduleChangeLog\Services;

use App\Core\Database;
use PDO;

final class ScheduleChangeLogger
{
    public static function recordIfEnabled(array $before, array $after, ?int $changedBy = null): void
    {
        try {
            $logger = new self();
            if (!$logger->moduleIsActive()) {
                return;
            }

            $logger->ensureSchema();
            $logger->record($before, $after, $changedBy);
        } catch (\Throwable) {
            // Change logging must never block schedule saving.
        }
    }

    public function record(array $before, array $after, ?int $changedBy = null): void
    {
        $items = $this->diffItems($before, $after);
        if (empty($items)) {
            return;
        }

        $scheduleId = (int) ($after['id'] ?? $before['id'] ?? 0);
        if ($scheduleId <= 0) {
            return;
        }

        $db = $this->db();
        $db->beginTransaction();

        try {
            $batchStmt = $db->prepare(
                'INSERT INTO schedule_change_batches (schedule_id, schedule_name, month, changed_by, summary)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $batchStmt->execute([
                $scheduleId,
                (string) ($after['schedule_name'] ?? $before['schedule_name'] ?? 'Grafiks'),
                (string) ($after['month'] ?? $before['month'] ?? ''),
                $changedBy,
                $this->summaryText($items),
            ]);
            $batchId = (int) $db->lastInsertId();

            $itemStmt = $db->prepare(
                'INSERT INTO schedule_change_items
                    (batch_id, schedule_id, user_id, employee_name, day_number, old_code, new_code, change_type)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($items as $item) {
                $itemStmt->execute([
                    $batchId,
                    $scheduleId,
                    $item['user_id'],
                    $item['employee_name'],
                    $item['day_number'],
                    $item['old_code'],
                    $item['new_code'],
                    $item['change_type'],
                ]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private function diffItems(array $before, array $after): array
    {
        $beforeEmployees = $this->employeeMap($before);
        $afterEmployees = $this->employeeMap($after);
        $keys = array_values(array_unique(array_merge(array_keys($beforeEmployees), array_keys($afterEmployees))));
        $items = [];

        foreach ($keys as $key) {
            $old = $beforeEmployees[$key] ?? null;
            $new = $afterEmployees[$key] ?? null;
            $base = $new ?: $old;
            if (!$base) {
                continue;
            }

            $userId = $base['user_id'] > 0 ? $base['user_id'] : null;
            $employeeName = $base['name'];

            if ($old === null && $new !== null) {
                $items[] = [
                    'user_id' => $userId,
                    'employee_name' => $employeeName,
                    'day_number' => null,
                    'old_code' => null,
                    'new_code' => null,
                    'change_type' => 'employee_added',
                ];
            }

            if ($old !== null && $new === null) {
                $items[] = [
                    'user_id' => $userId,
                    'employee_name' => $employeeName,
                    'day_number' => null,
                    'old_code' => null,
                    'new_code' => null,
                    'change_type' => 'employee_removed',
                ];
            }

            $oldShifts = $old['shifts'] ?? [];
            $newShifts = $new['shifts'] ?? [];
            $days = array_values(array_unique(array_merge(array_keys($oldShifts), array_keys($newShifts))));
            sort($days);

            foreach ($days as $day) {
                $oldCode = $oldShifts[$day] ?? '';
                $newCode = $newShifts[$day] ?? '';
                if ($oldCode === $newCode) {
                    continue;
                }

                $changeType = 'shift_changed';
                if ($oldCode === '') {
                    $changeType = 'shift_added';
                } elseif ($newCode === '') {
                    $changeType = 'shift_removed';
                }

                $items[] = [
                    'user_id' => $userId,
                    'employee_name' => $employeeName,
                    'day_number' => (int) $day,
                    'old_code' => $oldCode !== '' ? $oldCode : null,
                    'new_code' => $newCode !== '' ? $newCode : null,
                    'change_type' => $changeType,
                ];
            }
        }

        return $items;
    }

    private function employeeMap(array $schedule): array
    {
        $map = [];

        foreach (($schedule['employees'] ?? []) as $employee) {
            $userId = (int) ($employee['user_id'] ?? 0);
            $name = trim((string) ($employee['name'] ?? ''));
            if ($name === '') {
                $name = 'Darbinieks';
            }

            $key = $userId > 0
                ? 'user:' . $userId
                : 'manual:' . (int) ($employee['order_index'] ?? 0) . ':' . (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name));

            $shifts = [];
            foreach (($employee['shifts'] ?? []) as $shift) {
                $day = (int) ($shift['day_number'] ?? 0);
                if ($day < 1 || $day > 31) {
                    continue;
                }

                $shifts[$day] = strtoupper(trim((string) ($shift['shift_code'] ?? '')));
            }
            ksort($shifts);

            $map[$key] = [
                'user_id' => $userId,
                'name' => $name,
                'shifts' => $shifts,
            ];
        }

        return $map;
    }

    private function summaryText(array $items): string
    {
        $employeeNames = [];
        foreach ($items as $item) {
            $employeeNames[$item['employee_name']] = true;
        }

        $employeeCount = count($employeeNames);
        $changeCount = count($items);

        return sprintf('%d izmaiņas, %d darbinieki', $changeCount, $employeeCount);
    }

    private function moduleIsActive(): bool
    {
        try {
            $stmt = $this->db()->prepare('SELECT active FROM modules WHERE name = ? LIMIT 1');
            $stmt->execute(['ScheduleChangeLog']);
            $row = $stmt->fetch();

            return $row && (int) ($row['active'] ?? 0) === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureSchema(): void
    {
        $db = $this->db();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS schedule_change_batches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                schedule_id INT NOT NULL,
                schedule_name VARCHAR(255) NOT NULL,
                month VARCHAR(100) NULL,
                changed_by INT NULL,
                summary VARCHAR(255) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX schedule_change_batches_schedule_idx (schedule_id),
                INDEX schedule_change_batches_created_idx (created_at),
                INDEX schedule_change_batches_changed_by_idx (changed_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS schedule_change_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                batch_id INT NOT NULL,
                schedule_id INT NOT NULL,
                user_id INT NULL,
                employee_name VARCHAR(255) NOT NULL,
                day_number INT NULL,
                old_code VARCHAR(20) NULL,
                new_code VARCHAR(20) NULL,
                change_type ENUM('shift_added', 'shift_removed', 'shift_changed', 'employee_added', 'employee_removed') NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX schedule_change_items_batch_idx (batch_id),
                INDEX schedule_change_items_schedule_idx (schedule_id),
                INDEX schedule_change_items_user_idx (user_id),
                INDEX schedule_change_items_day_idx (day_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS schedule_change_reads (
                batch_id INT NOT NULL,
                user_id INT NOT NULL,
                read_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (batch_id, user_id),
                INDEX schedule_change_reads_user_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
