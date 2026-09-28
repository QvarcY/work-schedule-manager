-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS user_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_hash CHAR(64) NOT NULL UNIQUE,
    invited_contact VARCHAR(190) NULL,
    intended_role VARCHAR(50) NOT NULL DEFAULT 'employee',
    can_be_scheduled TINYINT(1) NOT NULL DEFAULT 1,
    schedule_view_mode ENUM('full', 'own', 'day', 'night') NOT NULL DEFAULT 'own',
    status ENUM('open', 'submitted', 'approved', 'rejected', 'cancelled', 'expired') NOT NULL DEFAULT 'open',
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    username VARCHAR(100) NULL,
    password_hash VARCHAR(255) NULL,
    admin_note TEXT NULL,
    created_by INT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    decided_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    INDEX user_invitations_status_index (status),
    INDEX user_invitations_expires_index (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
