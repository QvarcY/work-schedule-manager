<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ActivityLogger
{
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $message = null, ?array $user = null): void
    {
        try {
            $user = $user ?: auth()->user();
            self::ensureTable();
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
            // Logging must never break the main workflow.
        }
    }

    private static function ensureTable(): void
    {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                actor_name VARCHAR(190) NULL,
                action VARCHAR(120) NOT NULL,
                entity_type VARCHAR(120) NULL,
                entity_id INT NULL,
                message TEXT NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX activity_user_index (user_id),
                INDEX activity_action_index (action),
                INDEX activity_created_index (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
