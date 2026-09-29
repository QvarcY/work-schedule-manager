-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
ALTER TABLE users
    ADD COLUMN locale VARCHAR(12) NOT NULL DEFAULT 'lv' AFTER schedule_view_mode;
