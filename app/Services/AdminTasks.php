<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AdminTasks
{
    public function pending(): array
    {
        $tasks = [];

        $this->addIfPositive($tasks, 'Ielūgumi apstiprināšanai', '/user-invitations/admin', $this->countIfTableExists(
            'user_invitations',
            "SELECT COUNT(*) AS count FROM user_invitations WHERE status = 'submitted'"
        ));

        $this->addIfPositive($tasks, 'Brīvdienu pieteikumi', '/day-off-requests/admin', $this->countIfTableExists(
            'day_off_requests',
            "SELECT COUNT(*) AS count FROM day_off_requests WHERE status = 'pending'"
        ));

        return $tasks;
    }

    private function addIfPositive(array &$tasks, string $label, string $path, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $tasks[] = [
            'label' => $label,
            'path' => $path,
            'count' => $count,
        ];
    }

    private function countIfTableExists(string $table, string $sql): int
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT COUNT(*) AS count
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?'
            );
            $stmt->execute([$table]);

            if ((int) ($stmt->fetch()['count'] ?? 0) === 0) {
                return 0;
            }

            return (int) (Database::connection()->query($sql)->fetch()['count'] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
