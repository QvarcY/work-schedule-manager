<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\ScheduleChangeLog\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Services\ActivityLogger;
use PDO;

final class ScheduleChangeLogController
{
    public function index(): void
    {
        $user = auth()->requireLogin();
        $userId = (int) ($user['id'] ?? 0);

        view('schedule-change-log/index', [
            'title' => t('schedule_changes.title'),
            'batches' => $this->employeeBatches($userId),
            'itemsByBatch' => $this->employeeItemsByBatch($userId),
        ]);

        ActivityLogger::log('schedule_changes_viewed', 'schedule', null, 'schedule changes viewed', $user);
    }

    public function markRead(): void
    {
        $user = auth()->requireLogin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $batchId = (int) ($_POST['batch_id'] ?? 0);
        if ($batchId <= 0 || !$this->batchVisibleToUser($batchId, (int) $user['id'])) {
            throw new HttpException(403, t('schedule_changes.forbidden'));
        }

        $stmt = $this->db()->prepare(
            'INSERT INTO schedule_change_reads (batch_id, user_id, read_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE read_at = NOW()'
        );
        $stmt->execute([$batchId, (int) $user['id']]);

        Session::flash('success', t('schedule_changes.mark_read.success'));
        redirect('/schedule-changes');
    }

    public function adminIndex(): void
    {
        $admin = auth()->requireAdmin();
        $scheduleId = (int) ($_GET['schedule_id'] ?? 0);
        $userId = (int) ($_GET['user_id'] ?? 0);

        $batches = $this->adminBatches($scheduleId, $userId);
        $this->markBatchesReadForUser($batches, (int) $admin['id']);

        view('schedule-change-log/admin', [
            'title' => t('schedule_changes.admin.title'),
            'schedules' => $this->schedules(),
            'users' => $this->users(),
            'selectedScheduleId' => $scheduleId,
            'selectedUserId' => $userId,
            'batches' => $batches,
            'itemsByBatch' => $this->adminItemsByBatch($scheduleId, $userId),
        ]);

        ActivityLogger::log('schedule_changes_admin_viewed', 'schedule', $scheduleId ?: null, 'schedule change log viewed', $admin);
    }

    private function employeeBatches(int $userId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT b.*, u.username AS changed_by_username, u.first_name AS changed_by_first_name, u.last_name AS changed_by_last_name,
                    CASE WHEN r.read_at IS NULL THEN 0 ELSE 1 END AS is_read,
                    COUNT(i.id) AS item_count
             FROM schedule_change_batches b
             JOIN schedule_change_items i ON i.batch_id = b.id
             LEFT JOIN users u ON u.id = b.changed_by
             LEFT JOIN schedule_change_reads r ON r.batch_id = b.id AND r.user_id = ?
             WHERE i.user_id = ?
             GROUP BY b.id, u.username, u.first_name, u.last_name, r.read_at
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT 80"
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    private function employeeItemsByBatch(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT i.*
             FROM schedule_change_items i
             JOIN schedule_change_batches b ON b.id = i.batch_id
             WHERE i.user_id = ?
             ORDER BY b.created_at DESC, i.day_number IS NULL ASC, i.day_number ASC, i.id ASC'
        );
        $stmt->execute([$userId]);

        return $this->groupByBatch($stmt->fetchAll());
    }

    private function adminBatches(int $scheduleId, int $userId): array
    {
        [$where, $params] = $this->adminFilterWhere($scheduleId, $userId);
        $stmt = $this->db()->prepare(
            "SELECT b.*, u.username AS changed_by_username, u.first_name AS changed_by_first_name, u.last_name AS changed_by_last_name,
                    COUNT(i.id) AS item_count,
                    COUNT(DISTINCT i.user_id) AS affected_users
             FROM schedule_change_batches b
             JOIN schedule_change_items i ON i.batch_id = b.id
             LEFT JOIN users u ON u.id = b.changed_by
             {$where}
             GROUP BY b.id, u.username, u.first_name, u.last_name
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT 120"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function adminItemsByBatch(int $scheduleId, int $userId): array
    {
        [$where, $params] = $this->adminFilterWhere($scheduleId, $userId);
        $stmt = $this->db()->prepare(
            "SELECT i.*
             FROM schedule_change_items i
             JOIN schedule_change_batches b ON b.id = i.batch_id
             {$where}
             ORDER BY b.created_at DESC, i.employee_name ASC, i.day_number IS NULL ASC, i.day_number ASC, i.id ASC"
        );
        $stmt->execute($params);

        return $this->groupByBatch($stmt->fetchAll());
    }

    private function adminFilterWhere(int $scheduleId, int $userId): array
    {
        $where = [];
        $params = [];

        if ($scheduleId > 0) {
            $where[] = 'b.schedule_id = ?';
            $params[] = $scheduleId;
        }

        if ($userId > 0) {
            $where[] = 'i.user_id = ?';
            $params[] = $userId;
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function markBatchesReadForUser(array $batches, int $userId): void
    {
        if ($userId <= 0 || empty($batches)) {
            return;
        }

        $stmt = $this->db()->prepare(
            'INSERT INTO schedule_change_reads (batch_id, user_id, read_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE read_at = NOW()'
        );

        foreach ($batches as $batch) {
            $batchId = (int) ($batch['id'] ?? 0);
            if ($batchId > 0) {
                $stmt->execute([$batchId, $userId]);
            }
        }
    }
    private function groupByBatch(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            $grouped[(int) $item['batch_id']][] = $item;
        }

        return $grouped;
    }

    private function batchVisibleToUser(int $batchId, int $userId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) AS visible_count FROM schedule_change_items WHERE batch_id = ? AND user_id = ?');
        $stmt->execute([$batchId, $userId]);

        return (int) ($stmt->fetch()['visible_count'] ?? 0) > 0;
    }

    private function schedules(): array
    {
        return $this->db()->query('SELECT DISTINCT schedule_id AS id, schedule_name, month FROM schedule_change_batches ORDER BY created_at DESC, id DESC')->fetchAll();
    }

    private function users(): array
    {
        return $this->db()->query(
            "SELECT DISTINCT u.id, u.username, u.first_name, u.last_name
             FROM schedule_change_items i
             JOIN users u ON u.id = i.user_id
             ORDER BY u.first_name ASC, u.last_name ASC, u.username ASC"
        )->fetchAll();
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
