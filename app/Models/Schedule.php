<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Schedule extends Model
{
    public function latest(int $limit): array
    {
        $stmt = $this->db->prepare('SELECT * FROM schedules ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db
            ->query('SELECT * FROM schedules ORDER BY created_at DESC')
            ->fetchAll();
    }

    public function published(): array
    {
        return $this->db
            ->query("SELECT * FROM schedules WHERE status = 'published' ORDER BY created_at DESC")
            ->fetchAll();
    }

    public function create(string $name, string $month): int
    {
        $createdBy = $_SESSION['user_id'] ?? null;
        $stmt = $this->db->prepare('INSERT INTO schedules (schedule_name, month, created_by) VALUES (?, ?, ?)');
        $stmt->execute([$name, $month, $createdBy]);

        return (int) $this->db->lastInsertId();
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        $stmt = $this->db->prepare('UPDATE schedules SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM schedules WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function saveEditorData(int $id, string $name, string $month, string $status, array $employees, array $daySettings): void
    {
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        $this->ensureEmployeeUserIdColumn();
        $hasUserIdColumn = $this->hasColumn('employees', 'user_id');
        $validCodes = array_fill_keys(array_column($this->db->query('SELECT code FROM shift_types')->fetchAll(), 'code'), true);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('UPDATE schedules SET schedule_name = ?, month = ?, status = ? WHERE id = ?');
            $stmt->execute([$name, $month, $status, $id]);

            $stmt = $this->db->prepare('DELETE FROM employees WHERE schedule_id = ?');
            $stmt->execute([$id]);

            $stmt = $this->db->prepare('DELETE FROM schedule_day_settings WHERE schedule_id = ?');
            $stmt->execute([$id]);

            $dayStmt = $this->db->prepare(
                'INSERT INTO schedule_day_settings (schedule_id, day_number, day_type, label, background_color, text_color)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $normalizedDaySettings = [];
            foreach ($daySettings as $setting) {
                $day = (int) ($setting['day'] ?? 0);
                $type = (string) ($setting['type'] ?? 'normal');

                if ($day < 1 || $day > 31 || !in_array($type, ['normal', 'saturday', 'sunday', 'holiday'], true)) {
                    continue;
                }

                $normalizedDaySettings[$day] = $setting;
            }

            foreach ($normalizedDaySettings as $setting) {
                $day = (int) ($setting['day'] ?? 0);
                $type = (string) ($setting['type'] ?? 'normal');

                $backgroundColor = trim((string) ($setting['background_color'] ?? ''));
                $textColor = trim((string) ($setting['text_color'] ?? ''));

                if (
                    $type === 'normal'
                    && empty($setting['label'])
                    && ($backgroundColor === '' || $backgroundColor === '#ffffff')
                    && ($textColor === '' || $textColor === '#000000')
                ) {
                    continue;
                }

                $dayStmt->execute([
                    $id,
                    $day,
                    $type,
                    trim((string) ($setting['label'] ?? '')) ?: null,
                    $backgroundColor ?: (in_array($type, ['saturday', 'sunday'], true) ? '#d7e0ea' : null),
                    $textColor ?: null,
                ]);
            }

            $employeeStmt = $hasUserIdColumn
                ? $this->db->prepare('INSERT INTO employees (schedule_id, user_id, name, shifts_count, hours, order_index) VALUES (?, ?, ?, ?, ?, ?)')
                : $this->db->prepare('INSERT INTO employees (schedule_id, name, shifts_count, hours, order_index) VALUES (?, ?, ?, ?, ?)');
            $shiftStmt = $this->db->prepare(
                'INSERT INTO shifts (employee_id, day_number, shift_code, is_leader, note) VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($employees as $index => $employee) {
                $employeeName = trim((string) ($employee['name'] ?? ''));
                if ($employeeName === '') {
                    continue;
                }

                $userId = (int) ($employee['user_id'] ?? 0);
                $userId = $userId > 0 ? $userId : null;
                $shifts = is_array($employee['shifts'] ?? null) ? $employee['shifts'] : [];
                $summary = $this->calculateSummary($shifts, $validCodes);

                if ($hasUserIdColumn) {
                    $employeeStmt->execute([
                        $id,
                        $userId,
                        $employeeName,
                        $summary['shifts_count'],
                        $summary['hours'],
                        $index + 1,
                    ]);
                } else {
                    $employeeStmt->execute([
                        $id,
                        $employeeName,
                        $summary['shifts_count'],
                        $summary['hours'],
                        $index + 1,
                    ]);
                }

                $employeeId = (int) $this->db->lastInsertId();

                foreach ($shifts as $day => $shift) {
                    $dayNumber = (int) $day;
                    $code = strtoupper(trim((string) ($shift['code'] ?? '')));

                    if ($dayNumber < 1 || $dayNumber > 31 || $code === '' || !isset($validCodes[$code])) {
                        continue;
                    }

                    $shiftStmt->execute([
                        $employeeId,
                        $dayNumber,
                        $code,
                        str_contains($code, '*') ? 1 : 0,
                        trim((string) ($shift['note'] ?? '')) ?: null,
                    ]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function findWithEmployees(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM schedules WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            return null;
        }

        $employeeStmt = $this->db->prepare('SELECT * FROM employees WHERE schedule_id = ? ORDER BY order_index ASC, id ASC');
        $employeeStmt->execute([$id]);
        $employees = $employeeStmt->fetchAll();

        $shiftStmt = $this->db->prepare('SELECT * FROM shifts WHERE employee_id = ? ORDER BY day_number ASC');

        foreach ($employees as &$employee) {
            $shiftStmt->execute([(int) $employee['id']]);
            $employee['shifts'] = $shiftStmt->fetchAll();
        }

        $dayStmt = $this->db->prepare('SELECT * FROM schedule_day_settings WHERE schedule_id = ? ORDER BY day_number ASC');
        $dayStmt->execute([$id]);

        $schedule['employees'] = $employees;
        $schedule['day_settings'] = $dayStmt->fetchAll();

        return $schedule;
    }

    private function calculateSummary(array $shifts, array $validCodes): array
    {
        $types = [];
        $rows = $this->db->query('SELECT code, hours, counts_as_shift FROM shift_types')->fetchAll();
        foreach ($rows as $row) {
            $types[$row['code']] = $row;
        }

        $count = 0;
        $hours = 0.0;

        foreach ($shifts as $shift) {
            $code = strtoupper(trim((string) ($shift['code'] ?? '')));
            if ($code === '' || !isset($validCodes[$code], $types[$code])) {
                continue;
            }

            if ((int) $types[$code]['counts_as_shift'] === 1) {
                $count++;
            }

            $hours += (float) $types[$code]['hours'];
        }

        return [
            'shifts_count' => $count,
            'hours' => $hours,
        ];
    }

    private function ensureEmployeeUserIdColumn(): void
    {
        if ($this->hasColumn('employees', 'user_id')) {
            return;
        }

        $this->db->exec('ALTER TABLE employees ADD COLUMN user_id INT NULL AFTER schedule_id');

        try {
            $this->db->exec('ALTER TABLE employees ADD INDEX employees_user_index (user_id)');
        } catch (\Throwable) {
            // Index creation can fail if it already exists; the column is the important part.
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS column_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) ($stmt->fetch()['column_count'] ?? 0) > 0;
    }
}
