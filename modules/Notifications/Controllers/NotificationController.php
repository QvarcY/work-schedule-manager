<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\Notifications\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Services\ActivityLogger;
use Modules\Notifications\NotificationService;
use PDO;

final class NotificationController
{
    private const TYPES = [
        'info' => 'Info',
        'success' => 'Jaunums',
        'warning' => 'Svarīgi',
        'security' => 'Drošība',
        'schedule' => 'Grafiks',
        'account' => 'Konts',
    ];

    public function index(): void
    {
        $user = auth()->requireLogin();
        $service = new NotificationService();
        $service->ensureSchema();

        view('notifications/index', [
            'title' => 'Paziņojumi',
            'notifications' => $this->notificationsForUser((int) $user['id']),
            'preferences' => $this->preferencesForUser((int) $user['id']),
            'channels' => $this->channels(),
            'types' => self::TYPES,
        ]);
    }

    public function markRead(): void
    {
        $user = auth()->requireLogin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $this->db()->prepare('UPDATE notification_recipients SET read_at = COALESCE(read_at, NOW()) WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, (int) $user['id']]);

        redirect('/notifications');
    }

    public function markAllRead(): void
    {
        $user = auth()->requireLogin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $stmt = $this->db()->prepare('UPDATE notification_recipients SET read_at = COALESCE(read_at, NOW()) WHERE user_id = ? AND read_at IS NULL');
        $stmt->execute([(int) $user['id']]);

        redirect('/notifications');
    }

    public function savePreferences(): void
    {
        $user = auth()->requireLogin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $enabled = is_array($_POST['preferences'] ?? null) ? $_POST['preferences'] : [];
        $stmt = $this->db()->prepare(
            'INSERT INTO notification_preferences (user_id, channel_code, notification_type, enabled)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)'
        );

        foreach ($this->channels() as $channel) {
            foreach (array_keys(self::TYPES) as $type) {
                $isEnabled = isset($enabled[$channel['code']][$type]) ? 1 : 0;
                $stmt->execute([(int) $user['id'], $channel['code'], $type, $isEnabled]);
            }
        }

        Session::flash('success', 'Paziņojumu iestatījumi saglabāti.');
        redirect('/notifications');
    }

    public function adminIndex(): void
    {
        auth()->requireAdmin();
        (new NotificationService())->ensureSchema();

        view('notifications/admin', [
            'title' => 'Paziņojumu pārvaldība',
            'roles' => $this->roles(),
            'users' => $this->users(),
            'types' => self::TYPES,
            'recent' => $this->recentNotifications(),
        ]);
    }

    public function send(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $type = (string) ($_POST['type'] ?? 'info');
        $target = (string) ($_POST['target'] ?? 'all');

        if ($title === '' || $body === '') {
            Session::flash('error', 'Virsraksts un teksts ir obligāti.');
            redirect('/notifications/admin');
        }

        $service = new NotificationService();
        if ($target === 'user') {
            $service->createForUsers($title, $body, [(int) ($_POST['user_id'] ?? 0)], $type, (int) $admin['id']);
        } elseif ($target === 'role') {
            $service->createForRole($title, $body, (string) ($_POST['role'] ?? 'employee'), $type, (int) $admin['id']);
        } else {
            $service->createForAll($title, $body, $type, (int) $admin['id']);
        }

        ActivityLogger::log('notification_sent', 'notification', null, $title . ' / ' . $target, $admin);
        Session::flash('success', 'Paziņojums nosūtīts.');
        redirect('/notifications/admin');
    }

    private function notificationsForUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT nr.id AS recipient_id, nr.read_at, n.*
             FROM notification_recipients nr
             JOIN notifications n ON n.id = nr.notification_id
             WHERE nr.user_id = ?
             ORDER BY nr.read_at IS NULL DESC, n.created_at DESC
             LIMIT 80'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    private function preferencesForUser(int $userId): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM notification_preferences WHERE user_id = ?');
        $stmt->execute([$userId]);
        $map = [];

        foreach ($stmt->fetchAll() as $row) {
            $map[(string) $row['channel_code']][(string) $row['notification_type']] = (int) $row['enabled'] === 1;
        }

        return $map;
    }

    private function channels(): array
    {
        return $this->db()->query('SELECT * FROM notification_channels WHERE active = 1 ORDER BY label ASC')->fetchAll();
    }

    private function roles(): array
    {
        try {
            return $this->db()->query('SELECT code, label FROM roles ORDER BY sort_order ASC')->fetchAll();
        } catch (\Throwable) {
            return [
                ['code' => 'admin', 'label' => 'Admins'],
                ['code' => 'employee', 'label' => 'Darbinieks'],
                ['code' => 'user', 'label' => 'Lietotājs'],
            ];
        }
    }

    private function users(): array
    {
        return $this->db()->query('SELECT id, username, first_name, last_name, role FROM users ORDER BY username ASC')->fetchAll();
    }

    private function recentNotifications(): array
    {
        return $this->db()->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20')->fetchAll();
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
