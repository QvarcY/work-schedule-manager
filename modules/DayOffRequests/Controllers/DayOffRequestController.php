<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\DayOffRequests\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;

final class DayOffRequestController
{
    public function index(): void
    {
        $user = $this->requireEmployee();
        $requests = $this->requestsForUser((int) $user['id']);
        $this->markDecisionsSeen((int) $user['id']);

        view('day-off-requests/index', [
            'title' => t('day_off.title'),
            'requests' => $requests,
        ]);
    }

    public function store(): void
    {
        $user = $this->requireEmployee();
        Csrf::verify($_POST['_csrf'] ?? null);

        $date = trim((string) ($_POST['request_date'] ?? ''));
        $importance = trim((string) ($_POST['importance'] ?? 'velams'));
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if (!$this->isValidDate($date)) {
            Session::flash('error', t('day_off.validation.invalid_date'));
            redirect('/day-off-requests');
        }

        if ($date < date('Y-m-d')) {
            Session::flash('error', t('day_off.validation.past_date'));
            redirect('/day-off-requests');
        }

        if ($this->hasOpenRequest((int) $user['id'], $date)) {
            Session::flash('error', t('day_off.validation.duplicate'));
            redirect('/day-off-requests');
        }

        $requestId = $this->createRequest((int) $user['id'], $date, $importance, $comment);
        $this->log('day_off_requested', 'day_off_request', $requestId, 'date=' . $date, $user);

        Session::flash('success', t('day_off.create.success'));
        redirect('/day-off-requests');
    }

    public function adminIndex(): void
    {
        auth()->requireAdmin();
        $requests = $this->allRequestsForAdmin();

        view('day-off-requests/admin', [
            'title' => t('day_off.admin.title'),
            'requests' => $requests,
            'dateCounts' => $this->dateCounts($requests),
        ]);
    }

    public function decide(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $comment = trim((string) ($_POST['admin_comment'] ?? ''));

        if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            Session::flash('error', t('day_off.validation.invalid_decision'));
            redirect('/day-off-requests/admin');
        }

        $this->decideRequest($id, $status, (int) $admin['id'], $comment);
        $this->log('day_off_' . $status, 'day_off_request', $id, $comment, $admin);

        Session::flash('success', t('day_off.decision.success'));
        redirect('/day-off-requests/admin');
    }

    private function requireEmployee(): array
    {
        $user = auth()->requireLogin();

        if (($user['role'] ?? '') !== 'employee') {
            throw new HttpException(403, t('day_off.employee_only'));
        }

        return $user;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    private function hasOpenRequest(int $userId, string $date): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS request_count
             FROM day_off_requests
             WHERE user_id = ? AND request_date = ? AND status IN ('pending', 'approved')"
        );
        $stmt->execute([$userId, $date]);

        return (int) ($stmt->fetch()['request_count'] ?? 0) > 0;
    }

    private function createRequest(int $userId, string $date, string $importance, string $comment): int
    {
        $importance = in_array($importance, ['velams', 'svarigs', 'neatliekams'], true) ? $importance : 'velams';
        $stmt = Database::connection()->prepare(
            'INSERT INTO day_off_requests (user_id, request_date, importance, comment) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $date, $importance, $comment !== '' ? $comment : null]);

        return (int) Database::connection()->lastInsertId();
    }

    private function requestsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM day_off_requests WHERE user_id = ? ORDER BY request_date DESC, created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    private function allRequestsForAdmin(): array
    {
        return Database::connection()
            ->query('SELECT r.*, u.username, u.first_name, u.last_name FROM day_off_requests r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC')
            ->fetchAll();
    }

    private function decideRequest(int $id, string $status, int $adminId, string $comment): void
    {
        $status = in_array($status, ['approved', 'rejected'], true) ? $status : 'rejected';
        $stmt = Database::connection()->prepare(
            'UPDATE day_off_requests SET status = ?, decided_by = ?, decided_at = NOW(), admin_comment = ?, employee_seen_at = NULL WHERE id = ?'
        );
        $stmt->execute([$status, $adminId, $comment !== '' ? $comment : null, $id]);
    }

    private function markDecisionsSeen(int $userId): void
    {
        try {
            $stmt = Database::connection()->prepare(
                "UPDATE day_off_requests
                 SET employee_seen_at = NOW()
                 WHERE user_id = ?
                   AND status IN ('approved', 'rejected')
                   AND employee_seen_at IS NULL"
            );
            $stmt->execute([$userId]);
        } catch (\Throwable) {
            // Older installs without the seen column should still show the page.
        }
    }

    private function log(string $action, ?string $entityType, ?int $entityId, ?string $message, array $user): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO activity_logs (user_id, actor_name, action, entity_type, entity_id, message, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $user['id'] ?? null,
                $user['username'] ?? null,
                $action,
                $entityType,
                $entityId,
                $message,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (\Throwable) {
            // Logging must never break the request workflow.
        }
    }

    private function dateCounts(array $requests): array
    {
        $counts = [];

        foreach ($requests as $request) {
            $date = (string) ($request['request_date'] ?? '');
            if ($date === '') {
                continue;
            }

            $counts[$date] = ($counts[$date] ?? 0) + 1;
        }

        return $counts;
    }
}
