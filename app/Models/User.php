<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\AccessControl;

final class User extends Model
{
    public function all(): array
    {
        (new AccessControl())->ensureSchema();

        return $this->db
            ->query('SELECT * FROM users ORDER BY username ASC')
            ->fetchAll();
    }

    public function employees(): array
    {
        (new AccessControl())->ensureSchema();

        return $this->db
            ->query("SELECT * FROM users WHERE can_be_scheduled = 1 OR role = 'employee' ORDER BY first_name ASC, last_name ASC, username ASC")
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $username, string $password, string $role, array $profile = []): void
    {
        $access = new AccessControl();
        $access->ensureSchema();
        $role = in_array($role, $access->roleCodes(), true) ? $role : 'user';
        $canBeScheduled = !empty($profile['can_be_scheduled']) ? 1 : 0;
        $scheduleViewMode = in_array(($profile['schedule_view_mode'] ?? 'full'), ['full', 'own', 'day', 'night'], true)
            ? (string) $profile['schedule_view_mode']
            : 'full';
        $showHoursSummary = !empty($profile['show_hours_summary']) ? 1 : 0;

        $stmt = $this->db->prepare(
            'INSERT INTO users (username, first_name, last_name, email, phone, password_hash, role, can_be_scheduled, schedule_view_mode, show_hours_summary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $username,
            trim((string) ($profile['first_name'] ?? '')) ?: null,
            trim((string) ($profile['last_name'] ?? '')) ?: null,
            trim((string) ($profile['email'] ?? '')) ?: null,
            trim((string) ($profile['phone'] ?? '')) ?: null,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $canBeScheduled,
            $scheduleViewMode,
            $showHoursSummary,
        ]);
    }

    public function updateUser(int $id, string $username, string $role, string $password = '', array $profile = []): void
    {
        $access = new AccessControl();
        $access->ensureSchema();
        $role = in_array($role, $access->roleCodes(), true) ? $role : 'user';
        $canBeScheduled = !empty($profile['can_be_scheduled']) ? 1 : 0;
        $scheduleViewMode = in_array(($profile['schedule_view_mode'] ?? 'full'), ['full', 'own', 'day', 'night'], true)
            ? (string) $profile['schedule_view_mode']
            : 'full';
        $showHoursSummary = !empty($profile['show_hours_summary']) ? 1 : 0;
        $params = [
            $username,
            trim((string) ($profile['first_name'] ?? '')) ?: null,
            trim((string) ($profile['last_name'] ?? '')) ?: null,
            trim((string) ($profile['email'] ?? '')) ?: null,
            trim((string) ($profile['phone'] ?? '')) ?: null,
            $role,
            $canBeScheduled,
            $scheduleViewMode,
            $showHoursSummary,
        ];

        if ($password !== '') {
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $params[] = $id;
            $stmt = $this->db->prepare('UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, can_be_scheduled = ?, schedule_view_mode = ?, show_hours_summary = ?, password_hash = ? WHERE id = ?');
            $stmt->execute($params);
            return;
        }

        $params[] = $id;
        $stmt = $this->db->prepare('UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, can_be_scheduled = ?, schedule_view_mode = ?, show_hours_summary = ? WHERE id = ?');
        $stmt->execute($params);
    }

    public function updateProfile(int $id, array $profile, string $password = ''): void
    {
        $params = [
            trim((string) ($profile['first_name'] ?? '')) ?: null,
            trim((string) ($profile['last_name'] ?? '')) ?: null,
            trim((string) ($profile['email'] ?? '')) ?: null,
            trim((string) ($profile['phone'] ?? '')) ?: null,
        ];

        if ($password !== '') {
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $params[] = $id;
            $stmt = $this->db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?');
            $stmt->execute($params);
            return;
        }

        $params[] = $id;
        $stmt = $this->db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
