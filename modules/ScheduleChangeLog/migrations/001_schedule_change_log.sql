-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS schedule_change_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    schedule_name VARCHAR(255) NOT NULL,
    month VARCHAR(100) NULL,
    changed_by INT NULL,
    summary VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX schedule_change_batches_schedule_idx (schedule_id),
    INDEX schedule_change_batches_created_idx (created_at),
    INDEX schedule_change_batches_changed_by_idx (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_change_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    schedule_id INT NOT NULL,
    user_id INT NULL,
    employee_name VARCHAR(255) NOT NULL,
    day_number INT NULL,
    old_code VARCHAR(20) NULL,
    new_code VARCHAR(20) NULL,
    change_type ENUM('shift_added', 'shift_removed', 'shift_changed', 'employee_added', 'employee_removed') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX schedule_change_items_batch_idx (batch_id),
    INDEX schedule_change_items_schedule_idx (schedule_id),
    INDEX schedule_change_items_user_idx (user_id),
    INDEX schedule_change_items_day_idx (day_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_change_reads (
    batch_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (batch_id, user_id),
    INDEX schedule_change_reads_user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
