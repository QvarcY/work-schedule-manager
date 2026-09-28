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

final class ActivityLog extends Model
{
    private const ALLOWED_LIMITS = [25, 50, 100, 200];

    public function latest(int $limit = 200): array
    {
        $this->ensureTable();
        $stmt = $this->db->prepare('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function paginated(array $filters, int $page, int $perPage): array
    {
        $this->ensureTable();
        $perPage = in_array($perPage, self::ALLOWED_LIMITS, true) ? $perPage : 50;
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->filterSql($filters);

        $countStmt = $this->db->prepare('SELECT COUNT(*) AS total FROM activity_logs ' . $whereSql);
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $stmt = $this->db->prepare(
            'SELECT * FROM activity_logs ' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
        );

        $position = 1;
        foreach ($params as $value) {
            $stmt->bindValue($position, $value);
            $position++;
        }
        $stmt->bindValue($position, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue($position + 1, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'logs' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function actors(): array
    {
        $this->ensureTable();

        return $this->db
            ->query("SELECT actor_name, COUNT(*) AS log_count
                     FROM activity_logs
                     WHERE actor_name IS NOT NULL AND actor_name <> ''
                     GROUP BY actor_name
                     ORDER BY actor_name ASC")
            ->fetchAll();
    }

    public function stats(): array
    {
        $this->ensureTable();
        $row = $this->db
            ->query("SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS last_day,
                        SUM(CASE WHEN action LIKE 'login_%' THEN 1 ELSE 0 END) AS logins,
                        SUM(CASE WHEN action IN ('schedule_viewed', 'schedules_list_viewed', 'employee_profile_viewed', 'employee_profile_updated', 'day_off_requested') THEN 1 ELSE 0 END) AS employee_actions
                     FROM activity_logs")
            ->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'last_day' => (int) ($row['last_day'] ?? 0),
            'logins' => (int) ($row['logins'] ?? 0),
            'employee_actions' => (int) ($row['employee_actions'] ?? 0),
        ];
    }

    public function deleteOlderThan(string $period): int
    {
        $this->ensureTable();
        $days = [
            '30d' => 30,
            '90d' => 90,
            '180d' => 180,
            '365d' => 365,
        ][$period] ?? 0;

        if ($days <= 0) {
            return 0;
        }

        $stmt = $this->db->prepare('DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
        $stmt->bindValue(1, $days, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    private function filterSql(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['actor'])) {
            $where[] = 'actor_name = ?';
            $params[] = (string) $filters['actor'];
        }

        if (!empty($filters['action_group'])) {
            if ($filters['action_group'] === 'user') {
                $where[] = "action IN ('schedule_viewed', 'schedules_list_viewed', 'employee_profile_viewed', 'employee_profile_updated', 'day_off_requested', 'day_off_approved', 'day_off_rejected')";
            } elseif ($filters['action_group'] === 'admin') {
                $where[] = "action NOT IN ('schedule_viewed', 'schedules_list_viewed', 'employee_profile_viewed', 'employee_profile_updated', 'day_off_requested')";
            } elseif ($filters['action_group'] === 'login') {
                $where[] = "action LIKE 'login_%' OR action = 'logout'";
            }
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = (string) $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = (string) $filters['date_to'] . ' 23:59:59';
        }

        return [
            $where ? 'WHERE ' . implode(' AND ', array_map(static fn (string $item): string => '(' . $item . ')', $where)) : '',
            $params,
        ];
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS activity_logs (
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
                INDEX activity_created_index (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
