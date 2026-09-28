-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS day_off_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_date DATE NOT NULL,
    importance ENUM('velams', 'svarigs', 'neatliekams') NOT NULL DEFAULT 'velams',
    comment TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_comment TEXT NULL,
    employee_seen_at TIMESTAMP NULL,
    decided_by INT NULL,
    decided_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX day_off_user_index (user_id),
    INDEX day_off_status_index (status),
    INDEX day_off_date_index (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
