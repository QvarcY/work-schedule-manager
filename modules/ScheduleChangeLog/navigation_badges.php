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
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return [];
        }

        if (($user['role'] ?? '') === 'admin') {
            $stmt = Database::connection()->prepare(
                'SELECT COUNT(*) AS count
                 FROM schedule_change_batches b
                 LEFT JOIN schedule_change_reads r ON r.batch_id = b.id AND r.user_id = ?
                 WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                   AND r.read_at IS NULL'
            );
            $stmt->execute([$userId]);
            $count = (int) ($stmt->fetch()['count'] ?? 0);

            return [
                '/schedule-changes/admin' => [
                    'count' => min($count, 99),
                    'tone' => $count > 0 ? 'danger' : 'info',
                ],
            ];
        }

        if ($userId <= 0) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT b.id) AS count
             FROM schedule_change_batches b
             JOIN schedule_change_items i ON i.batch_id = b.id
             LEFT JOIN schedule_change_reads r ON r.batch_id = b.id AND r.user_id = ?
             WHERE i.user_id = ?
               AND r.read_at IS NULL'
        );
        $stmt->execute([$userId, $userId]);
        $count = (int) ($stmt->fetch()['count'] ?? 0);

        return [
            '/schedule-changes' => [
                'count' => min($count, 99),
                'tone' => $count > 0 ? 'danger' : 'info',
            ],
        ];
    } catch (Throwable) {
        return [];
    }
};
