<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\ScheduleAcknowledgements\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Services\ActivityLogger;

final class ScheduleAcknowledgementController
{
    public function index(): void
    {
        $user = $this->requireEmployee();

        view('schedule-acknowledgements/index', [
            'title' => t('acknowledgements.title'),
            'items' => $this->employeeSchedules((int) $user['id']),
        ]);
    }

    public function acknowledge(): void
    {
        $user = $this->requireEmployee();
        Csrf::verify($_POST['_csrf'] ?? null);

        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if ($scheduleId <= 0 || !$this->isAssignedToSchedule((int) $user['id'], $scheduleId)) {
            throw new HttpException(403, t('acknowledgements.forbidden'));
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO schedule_acknowledgements (schedule_id, user_id, comment, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                acknowledged_at = NOW(),
                comment = VALUES(comment),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)'
        );
        $stmt->execute([
            $scheduleId,
            (int) $user['id'],
            $comment !== '' ? $comment : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        ActivityLogger::log('schedule_acknowledged', 'schedule', $scheduleId, 'schedule acknowledged', $user);
        Session::flash('success', t('acknowledgements.save_success'));
        redirect('/schedule-acknowledgements');
    }

    public function adminIndex(): void
    {
        $admin = auth()->requireAdmin();
        $scheduleId = (int) ($_GET['schedule_id'] ?? 0);

        view('schedule-acknowledgements/admin', [
            'title' => t('acknowledgements.admin.title'),
            'schedules' => $this->publishedSchedules(),
            'selectedScheduleId' => $scheduleId,
            'rows' => $scheduleId > 0 ? $this->adminRows($scheduleId) : [],
        ]);

        ActivityLogger::log('schedule_acknowledgements_viewed', 'schedule', $scheduleId ?: null, 'schedule acknowledgements viewed', $admin);
    }

    private function requireEmployee(): array
    {
        $user = auth()->requireLogin();

        if (($user['role'] ?? '') !== 'employee' && (int) ($user['can_be_scheduled'] ?? 0) !== 1) {
            throw new HttpException(403, t('acknowledgements.employee_only'));
        }

        return $user;
    }

    private function employeeSchedules(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                s.id,
                s.schedule_name,
                s.month,
                s.status,
                s.updated_at,
                e.name AS employee_schedule_name,
                e.shifts_count,
                e.hours,
                a.acknowledged_at,
                a.comment,
                CASE
                    WHEN a.acknowledged_at IS NOT NULL AND a.acknowledged_at >= s.updated_at THEN 1
                    ELSE 0
                END AS acknowledgement_current
             FROM schedules s
             JOIN employees e ON e.schedule_id = s.id
             LEFT JOIN schedule_acknowledgements a
                ON a.schedule_id = s.id AND a.user_id = ?
             WHERE s.status = 'published'
               AND e.user_id = ?
             GROUP BY s.id, s.schedule_name, s.month, s.status, s.updated_at, e.name, e.shifts_count, e.hours, a.acknowledged_at, a.comment
             ORDER BY s.updated_at DESC, s.id DESC"
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    private function isAssignedToSchedule(int $userId, int $scheduleId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS assigned_count
             FROM schedules s
             JOIN employees e ON e.schedule_id = s.id
             WHERE s.id = ?
               AND s.status = 'published'
               AND e.user_id = ?"
        );
        $stmt->execute([$scheduleId, $userId]);

        return (int) ($stmt->fetch()['assigned_count'] ?? 0) > 0;
    }

    private function publishedSchedules(): array
    {
        return Database::connection()
            ->query("SELECT id, schedule_name, month, updated_at
                     FROM schedules
                     WHERE status = 'published'
                     ORDER BY updated_at DESC, id DESC")
            ->fetchAll();
    }

    private function adminRows(int $scheduleId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT
                u.id AS user_id,
                u.username,
                u.first_name,
                u.last_name,
                e.name AS schedule_name,
                s.updated_at,
                a.acknowledged_at,
                a.comment,
                a.ip_address,
                CASE
                    WHEN a.acknowledged_at IS NOT NULL AND a.acknowledged_at >= s.updated_at THEN 1
                    ELSE 0
                END AS acknowledgement_current
             FROM employees e
             JOIN schedules s ON s.id = e.schedule_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN schedule_acknowledgements a
                ON a.schedule_id = e.schedule_id AND a.user_id = e.user_id
             WHERE e.schedule_id = ?
               AND e.user_id IS NOT NULL
             GROUP BY u.id, u.username, u.first_name, u.last_name, e.name, s.updated_at, a.acknowledged_at, a.comment, a.ip_address
             ORDER BY a.acknowledged_at IS NULL DESC, u.first_name ASC, u.last_name ASC, u.username ASC"
        );
        $stmt->execute([$scheduleId]);

        return $stmt->fetchAll();
    }
}
