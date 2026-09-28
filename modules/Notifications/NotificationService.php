<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\Notifications;

use App\Core\Database;
use PDO;

final class NotificationService
{
    public const TYPES = ['info', 'success', 'warning', 'security', 'schedule', 'account'];

    public function createForUsers(string $title, string $body, array $userIds, string $type = 'info', ?int $createdBy = null): int
    {
        $this->ensureSchema();
        $type = in_array($type, self::TYPES, true) ? $type : 'info';
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        $stmt = $this->db()->prepare('INSERT INTO notifications (title, body, type, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $body, $type, $createdBy]);
        $notificationId = (int) $this->db()->lastInsertId();

        $recipientStmt = $this->db()->prepare('INSERT IGNORE INTO notification_recipients (notification_id, user_id) VALUES (?, ?)');
        $deliveryStmt = $this->db()->prepare(
            "INSERT INTO notification_deliveries (notification_id, user_id, channel_code, status)
             VALUES (?, ?, 'system', 'sent')"
        );
        foreach ($userIds as $userId) {
            $recipientStmt->execute([$notificationId, $userId]);
            $deliveryStmt->execute([$notificationId, $userId]);
        }

        $this->deliverExternalChannels($notificationId, $userIds);

        return $notificationId;
    }

    public function createForRole(string $title, string $body, string $role, string $type = 'info', ?int $createdBy = null): int
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare('SELECT id FROM users WHERE role = ? ORDER BY id ASC');
        $stmt->execute([$role]);

        return $this->createForUsers($title, $body, array_column($stmt->fetchAll(), 'id'), $type, $createdBy);
    }

    public function createForAll(string $title, string $body, string $type = 'info', ?int $createdBy = null): int
    {
        $this->ensureSchema();
        $rows = $this->db()->query('SELECT id FROM users ORDER BY id ASC')->fetchAll();

        return $this->createForUsers($title, $body, array_column($rows, 'id'), $type, $createdBy);
    }

    public function unreadCount(int $userId): int
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare('SELECT COUNT(*) AS count FROM notification_recipients WHERE user_id = ? AND read_at IS NULL');
        $stmt->execute([$userId]);

        return (int) ($stmt->fetch()['count'] ?? 0);
    }

    public function ensureSchema(): void
    {
        $db = $this->db();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(190) NOT NULL,
                body TEXT NOT NULL,
                type ENUM('info', 'success', 'warning', 'security', 'schedule', 'account') NOT NULL DEFAULT 'info',
                created_by INT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notification_recipients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_id INT NOT NULL,
                user_id INT NOT NULL,
                read_at TIMESTAMP NULL,
                dismissed_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY notification_user_unique (notification_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notification_channels (
                code VARCHAR(50) PRIMARY KEY,
                label VARCHAR(100) NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notification_preferences (
                user_id INT NOT NULL,
                channel_code VARCHAR(50) NOT NULL,
                notification_type VARCHAR(50) NOT NULL,
                enabled TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (user_id, channel_code, notification_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec(
            "CREATE TABLE IF NOT EXISTS notification_deliveries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_id INT NOT NULL,
                user_id INT NOT NULL,
                channel_code VARCHAR(50) NOT NULL,
                status ENUM('pending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending',
                error_message TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                sent_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec("INSERT INTO notification_channels (code, label, active) VALUES ('system', 'Sistēmā', 1) ON DUPLICATE KEY UPDATE label = VALUES(label), active = VALUES(active)");
    }

    private function deliverExternalChannels(int $notificationId, array $userIds): void
    {
        if (!class_exists('\Modules\EmailNotifications\EmailNotificationChannel')) {
            return;
        }

        try {
            if (!$this->moduleIsActive('EmailNotifications')) {
                return;
            }

            (new \Modules\EmailNotifications\EmailNotificationChannel())->deliver($notificationId, $userIds);
        } catch (\Throwable) {
            // External channels must never break the in-app notification flow.
        }
    }

    private function moduleIsActive(string $moduleName): bool
    {
        try {
            $stmt = $this->db()->prepare('SELECT active FROM modules WHERE name = ? LIMIT 1');
            $stmt->execute([$moduleName]);
            $row = $stmt->fetch();

            return $row && (int) ($row['active'] ?? 0) === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
