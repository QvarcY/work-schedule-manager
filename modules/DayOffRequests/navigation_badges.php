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
        if (($user['role'] ?? '') === 'admin') {
            $count = (int) Database::connection()
                ->query("SELECT COUNT(*) AS request_count FROM day_off_requests WHERE status = 'pending'")
                ->fetch()['request_count'];

            return [
                '/day-off-requests/admin' => [
                    'count' => $count,
                    'tone' => $count > 0 ? 'danger' : 'info',
                ],
            ];
        }

        if (($user['role'] ?? '') === 'employee') {
            $stmt = Database::connection()->prepare(
                "SELECT COUNT(*) AS request_count
                 FROM day_off_requests
                 WHERE user_id = ?
                   AND status IN ('approved', 'rejected')
                   AND employee_seen_at IS NULL"
            );
            $stmt->execute([(int) $user['id']]);
            $count = (int) $stmt->fetch()['request_count'];

            return [
                '/day-off-requests' => [
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
