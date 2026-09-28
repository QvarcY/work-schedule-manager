-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
CREATE TABLE IF NOT EXISTS schema_migrations (
    migration VARCHAR(190) PRIMARY KEY,
    applied_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM(
        'admin',
        'moderator',
        'control',
        'employee',
        'viewer',
        'user'
    ) NOT NULL DEFAULT 'user',
    can_be_scheduled TINYINT(1) NOT NULL DEFAULT 0,
    schedule_view_mode ENUM('full', 'own', 'day', 'night') NOT NULL DEFAULT 'full',
    show_hours_summary TINYINT(1) NOT NULL DEFAULT 0,
    receive_all_schedule_updates TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX users_role_index (role),
    INDEX users_schedulable_index (can_be_scheduled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(255) NOT NULL,
    month VARCHAR(100) NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    created_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX schedules_status_index (status),
    CONSTRAINT schedules_created_by_fk
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    shifts_count INT NOT NULL DEFAULT 0,
    hours DECIMAL(7,2) NOT NULL DEFAULT 0,
    order_index INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX employees_schedule_index (schedule_id),
    INDEX employees_user_index (user_id),
    CONSTRAINT employees_schedule_fk
        FOREIGN KEY (schedule_id)
        REFERENCES schedules(id)
        ON DELETE CASCADE,
    CONSTRAINT employees_user_fk
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shift_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    hours DECIMAL(5,2) NOT NULL DEFAULT 0,
    counts_as_shift TINYINT(1) NOT NULL DEFAULT 1,
    background_color VARCHAR(20) NOT NULL DEFAULT '#ffffff',
    text_color VARCHAR(20) NOT NULL DEFAULT '#000000',
    is_leader_type TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    day_number INT NOT NULL,
    shift_code VARCHAR(10) NOT NULL,
    is_leader TINYINT(1) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY shifts_employee_day_unique (employee_id, day_number),
    INDEX shifts_shift_code_index (shift_code),
    CONSTRAINT shifts_employee_fk
        FOREIGN KEY (employee_id)
        REFERENCES employees(id)
        ON DELETE CASCADE,
    CONSTRAINT shifts_shift_type_fk
        FOREIGN KEY (shift_code)
        REFERENCES shift_types(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_day_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    day_number INT NOT NULL,
    day_type ENUM('normal', 'saturday', 'sunday', 'holiday') NOT NULL DEFAULT 'normal',
    label VARCHAR(100) NULL,
    background_color VARCHAR(20) NULL,
    text_color VARCHAR(20) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY schedule_day_unique (schedule_id, day_number),
    CONSTRAINT schedule_day_schedule_fk
        FOREIGN KEY (schedule_id)
        REFERENCES schedules(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    source VARCHAR(255) NULL,
    installed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    code VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    code VARCHAR(100) PRIMARY KEY,
    label VARCHAR(160) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_code VARCHAR(50) NOT NULL,
    permission_code VARCHAR(100) NOT NULL,
    PRIMARY KEY (role_code, permission_code),
    CONSTRAINT role_permissions_role_fk
        FOREIGN KEY (role_code)
        REFERENCES roles(code)
        ON DELETE CASCADE,
    CONSTRAINT role_permissions_permission_fk
        FOREIGN KEY (permission_code)
        REFERENCES permissions(code)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
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
    INDEX activity_created_index (created_at),
    CONSTRAINT activity_user_fk
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
