-- Work Schedule Manager
-- Copyright (C) 2026 QvarcY
-- SPDX-License-Identifier: AGPL-3.0-or-later
-- Additional terms: see ADDITIONAL_TERMS.md
INSERT INTO shift_types
    (
        code,
        label,
        hours,
        counts_as_shift,
        background_color,
        text_color,
        is_leader_type,
        sort_order,
        active
    )
VALUES
    ('D',   'Dienas maiņa',                 12, 1, '#f59e0b', '#111827', 0,  10, 1),
    ('N',   'Nakts maiņa',                  12, 1, '#2563eb', '#ffffff', 0,  20, 1),
    ('DT',  'Dienas īsā maiņa',              4, 1, '#fbbf24', '#111827', 0,  30, 1),
    ('NT',  'Nakts īsā maiņa',               4, 1, '#1d4ed8', '#ffffff', 0,  40, 1),
    ('A',   'Atvaļinājums',                  0, 0, '#bbf7d0', '#14532d', 0,  50, 1),
    ('S',   'Slimības lapa',                 0, 0, '#e5e7eb', '#111827', 0,  60, 1),
    ('X',   'Brīva / nav pieejams',          0, 0, '#f3f4f6', '#4b5563', 0,  70, 1),
    ('D*',  'Dienas maiņas vadītājs',       12, 1, '#fde68a', '#111827', 1,  80, 1),
    ('N*',  'Nakts maiņas vadītājs',        12, 1, '#fde68a', '#111827', 1,  90, 1),
    ('DT*', 'Dienas īsās maiņas vadītājs',   4, 1, '#fde68a', '#111827', 1, 100, 1),
    ('NT*', 'Nakts īsās maiņas vadītājs',    4, 1, '#fde68a', '#111827', 1, 110, 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    hours = VALUES(hours),
    counts_as_shift = VALUES(counts_as_shift),
    background_color = VALUES(background_color),
    text_color = VALUES(text_color),
    is_leader_type = VALUES(is_leader_type),
    sort_order = VALUES(sort_order),
    active = VALUES(active);
