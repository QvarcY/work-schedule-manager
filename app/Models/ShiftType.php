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

final class ShiftType extends Model
{
    public function activeByCode(): array
    {
        $rows = $this->db
            ->query('SELECT * FROM shift_types WHERE active = 1 ORDER BY sort_order ASC, code ASC')
            ->fetchAll();

        $types = [];
        foreach ($rows as $row) {
            $types[$row['code']] = $row;
        }

        return $types;
    }

    public function all(): array
    {
        return $this->db
            ->query('SELECT * FROM shift_types ORDER BY sort_order ASC, code ASC')
            ->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO shift_types (code, label, hours, counts_as_shift, background_color, text_color, is_leader_type, sort_order, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute($this->payload($data));
    }

    public function update(int $id, array $data): void
    {
        $payload = $this->payload($data);
        $payload[] = $id;

        $stmt = $this->db->prepare(
            'UPDATE shift_types
             SET code = ?, label = ?, hours = ?, counts_as_shift = ?, background_color = ?, text_color = ?, is_leader_type = ?, sort_order = ?, active = ?
             WHERE id = ?'
        );
        $stmt->execute($payload);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT code FROM shift_types WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $type = $stmt->fetch();

        if (!$type) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) AS used_count FROM shifts WHERE shift_code = ?');
        $stmt->execute([$type['code']]);
        $usedCount = (int) ($stmt->fetch()['used_count'] ?? 0);

        if ($usedCount > 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM shift_types WHERE id = ?');
        $stmt->execute([$id]);

        return true;
    }

    private function payload(array $data): array
    {
        return [
            strtoupper(trim((string) ($data['code'] ?? ''))),
            trim((string) ($data['label'] ?? '')),
            (float) ($data['hours'] ?? 0),
            isset($data['counts_as_shift']) ? 1 : 0,
            trim((string) ($data['background_color'] ?? '#ffffff')) ?: '#ffffff',
            trim((string) ($data['text_color'] ?? '#000000')) ?: '#000000',
            isset($data['is_leader_type']) ? 1 : 0,
            (int) ($data['sort_order'] ?? 0),
            isset($data['active']) ? 1 : 0,
        ];
    }
}
