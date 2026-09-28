-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'security', 'schedule', 'account') NOT NULL DEFAULT 'info',
    created_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX notifications_type_index (type),
    INDEX notifications_created_index (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP NULL,
    dismissed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY notification_user_unique (notification_id, user_id),
    INDEX notification_recipient_user_index (user_id),
    INDEX notification_recipient_read_index (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_channels (
    code VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT NOT NULL,
    channel_code VARCHAR(50) NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, channel_code, notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    channel_code VARCHAR(50) NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX notification_deliveries_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO notification_channels (code, label, active)
VALUES ('system', 'Sistēmā', 1)
ON DUPLICATE KEY UPDATE label = VALUES(label), active = VALUES(active);
