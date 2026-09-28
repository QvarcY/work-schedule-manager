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
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS count
             FROM notification_recipients
             WHERE user_id = ?
               AND read_at IS NULL'
        );
        $stmt->execute([(int) $user['id']]);
        $count = (int) ($stmt->fetch()['count'] ?? 0);

        return [
            '/notifications' => [
                'count' => $count,
                'tone' => $count > 0 ? 'danger' : 'info',
            ],
        ];
    } catch (Throwable) {
        return [];
    }
};
