-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS schedule_acknowledgements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    acknowledged_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    comment TEXT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY schedule_ack_user_unique (schedule_id, user_id),
    INDEX schedule_ack_schedule_index (schedule_id),
    INDEX schedule_ack_user_index (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
