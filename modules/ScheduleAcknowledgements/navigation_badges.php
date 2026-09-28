<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use App\Core\Database;

return static function (?array $user): array {
    if (!$user) {
        return [];
    }

    try {
        if (($user['role'] ?? '') === 'employee' || (int) ($user['can_be_scheduled'] ?? 0) === 1) {
            $stmt = Database::connection()->prepare(
                "SELECT COUNT(DISTINCT s.id) AS pending_count
                 FROM schedules s
                 JOIN employees e ON e.schedule_id = s.id
                 LEFT JOIN schedule_acknowledgements a
                    ON a.schedule_id = s.id AND a.user_id = ?
                 WHERE s.status = 'published'
                   AND e.user_id = ?
                   AND (a.id IS NULL OR a.acknowledged_at < s.updated_at)"
            );
            $stmt->execute([(int) $user['id'], (int) $user['id']]);
            $count = (int) ($stmt->fetch()['pending_count'] ?? 0);

            return [
                '/schedule-acknowledgements' => [
                    'count' => $count,
                    'tone' => $count > 0 ? 'danger' : 'info',
                ],
            ];
        }

        if (($user['role'] ?? '') === 'admin') {
            $count = (int) Database::connection()
                ->query(
                    "SELECT COUNT(*) AS pending_count
                     FROM (
                        SELECT DISTINCT s.id AS schedule_id, e.user_id
                        FROM schedules s
                        JOIN employees e ON e.schedule_id = s.id
                        LEFT JOIN schedule_acknowledgements a
                           ON a.schedule_id = s.id AND a.user_id = e.user_id
                        WHERE s.status = 'published'
                          AND e.user_id IS NOT NULL
                          AND (a.id IS NULL OR a.acknowledged_at < s.updated_at)
                     ) pending"
                )
                ->fetch()['pending_count'];

            return [
                '/schedule-acknowledgements/admin' => [
                    'count' => $count,
                    'tone' => $count > 0 ? 'danger' : 'info',
                ],
            ];
        }
    } catch (Throwable) {
        return [];
    }

    return [];
};
