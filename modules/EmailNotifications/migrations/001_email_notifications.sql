-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS notification_channels (
    code VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_notification_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO notification_channels (code, label, active)
VALUES ('email', 'E-pasts', 1)
ON DUPLICATE KEY UPDATE label = VALUES(label), active = VALUES(active);
