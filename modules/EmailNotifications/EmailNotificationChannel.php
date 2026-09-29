<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\EmailNotifications;

use App\Core\Database;
use App\Core\Env;
use PDO;

final class EmailNotificationChannel
{
    private const DEFAULT_SETTINGS = [
        'enabled' => '0',
        'from_email' => '',
        'from_name' => '',
        'reply_to' => '',
        'subject_prefix' => '[Work Schedule]',
    ];

    public function ensureSchema(): void
    {
        $db = $this->db();
        $db->exec(
            "CREATE TABLE IF NOT EXISTS email_notification_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $db->exec("INSERT INTO notification_channels (code, label, active) VALUES ('email', 'Email', 1) ON DUPLICATE KEY UPDATE label = VALUES(label), active = VALUES(active)");

        $defaults = self::DEFAULT_SETTINGS;
        $defaults['enabled'] = Env::bool('EMAIL_NOTIFICATIONS_ENABLED', false) ? '1' : '0';
        $defaults['from_email'] = (string) Env::get('EMAIL_FROM_ADDRESS', $defaults['from_email']);
        $defaults['from_name'] = (string) Env::get('EMAIL_FROM_NAME', Env::get('APP_NAME', 'Work Schedule Manager'));
        $defaults['reply_to'] = (string) Env::get('EMAIL_REPLY_TO', $defaults['reply_to']);

        $stmt = $db->prepare('INSERT IGNORE INTO email_notification_settings (setting_key, setting_value) VALUES (?, ?)');
        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }

    public function settings(): array
    {
        $this->ensureSchema();
        $settings = self::DEFAULT_SETTINGS;
        $rows = $this->db()->query('SELECT setting_key, setting_value FROM email_notification_settings')->fetchAll();
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function saveSettings(array $settings): void
    {
        $this->ensureSchema();
        $allowed = array_keys(self::DEFAULT_SETTINGS);
        $stmt = $this->db()->prepare(
            'INSERT INTO email_notification_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($allowed as $key) {
            $value = (string) ($settings[$key] ?? '');
            if ($key === 'enabled') {
                $value = !empty($settings[$key]) ? '1' : '0';
            }
            $stmt->execute([$key, $value]);
        }
    }

    public function deliver(int $notificationId, array $userIds): void
    {
        $this->ensureSchema();
        $settings = $this->settings();
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if ($notificationId <= 0 || empty($userIds)) {
            return;
        }

        $notification = $this->notification($notificationId);
        if (!$notification) {
            return;
        }

        foreach ($this->users($userIds) as $user) {
            $userId = (int) $user['id'];
            if ($this->alreadyDelivered($notificationId, $userId)) {
                continue;
            }

            if (($settings['enabled'] ?? '0') !== '1') {
                $this->recordDelivery($notificationId, $userId, 'skipped', t('email_notifications.errors.disabled'));
                continue;
            }

            if (!$this->preferenceEnabled($userId, (string) $notification['type'])) {
                $this->recordDelivery($notificationId, $userId, 'skipped', t('email_notifications.errors.user_disabled'));
                continue;
            }

            $email = trim((string) ($user['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->recordDelivery($notificationId, $userId, 'skipped', t('email_notifications.errors.user_email_missing'));
                continue;
            }

            $result = $this->sendEmail($email, (string) $notification['title'], (string) $notification['body'], $settings);
            $this->recordDelivery($notificationId, $userId, $result['sent'] ? 'sent' : 'failed', $result['sent'] ? null : $result['error']);
        }
    }

    public function sendTestEmail(string $email): array
    {
        $this->ensureSchema();
        $settings = $this->settings();

        if (($settings['enabled'] ?? '0') !== '1') {
            return ['sent' => false, 'error' => t('email_notifications.errors.disabled')];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => t('email_notifications.errors.invalid_recipient')];
        }

        return $this->sendEmail(
            $email,
            t('email_notifications.test.message_title'),
            t('email_notifications.test.message_body', ['app' => $this->applicationName()]),
            $settings
        );
    }

    public function recentDeliveries(int $limit = 40): array
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare(
            "SELECT nd.*, n.title, n.type, u.username, u.email
             FROM notification_deliveries nd
             JOIN notifications n ON n.id = nd.notification_id
             JOIN users u ON u.id = nd.user_id
             WHERE nd.channel_code = 'email'
             ORDER BY nd.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function sendEmail(string $to, string $title, string $body, array $settings): array
    {
        $fromEmail = trim((string) ($settings['from_email'] ?? ''));
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'error' => t('email_notifications.errors.sender_missing')];
        }

        $fromName = trim((string) ($settings['from_name'] ?? '')) ?: $this->applicationName();
        $prefix = trim((string) ($settings['subject_prefix'] ?? ''));
        $subject = trim($prefix . ' ' . $title);
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
        $message = $body;
        if ($appUrl !== '') {
            $message .= "\n\n" . t('email_notifications.open_system', ['url' => $appUrl]);
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->mailboxHeader($fromName, $fromEmail),
        ];

        $replyTo = trim((string) ($settings['reply_to'] ?? ''));
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $encodedSubject = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($subject, 'UTF-8') : $subject;
        $sent = @mail($to, $encodedSubject, $message, implode("\r\n", $headers));

        return [
            'sent' => $sent,
            'error' => $sent ? null : t('email_notifications.errors.mail_failed'),
        ];
    }

    private function applicationName(): string
    {
        $name = trim((string) Env::get(
            'APP_NAME',
            'Work Schedule Manager'
        ));

        return $name !== ''
            ? $name
            : 'Work Schedule Manager';
    }
    private function mailboxHeader(string $name, string $email): string
    {
        $encodedName = function_exists('mb_encode_mimeheader') ? mb_encode_mimeheader($name, 'UTF-8') : $name;

        return $encodedName . ' <' . $email . '>';
    }

    private function recordDelivery(int $notificationId, int $userId, string $status, ?string $error = null): void
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO notification_deliveries (notification_id, user_id, channel_code, status, error_message, sent_at)
             VALUES (?, ?, 'email', ?, ?, CASE WHEN ? = 'sent' THEN NOW() ELSE NULL END)"
        );
        $stmt->execute([$notificationId, $userId, $status, $error, $status]);
    }

    private function alreadyDelivered(int $notificationId, int $userId): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) AS count
             FROM notification_deliveries
             WHERE notification_id = ? AND user_id = ? AND channel_code = 'email'"
        );
        $stmt->execute([$notificationId, $userId]);

        return (int) ($stmt->fetch()['count'] ?? 0) > 0;
    }

    private function preferenceEnabled(int $userId, string $type): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT enabled FROM notification_preferences
             WHERE user_id = ? AND channel_code = 'email' AND notification_type = ?
             LIMIT 1"
        );
        $stmt->execute([$userId, $type]);
        $row = $stmt->fetch();

        return !$row || (int) ($row['enabled'] ?? 1) === 1;
    }

    private function notification(int $notificationId): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM notifications WHERE id = ? LIMIT 1');
        $stmt->execute([$notificationId]);

        return $stmt->fetch() ?: null;
    }

    private function users(array $userIds): array
    {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db()->prepare("SELECT id, username, email FROM users WHERE id IN ($placeholders)");
        $stmt->execute($userIds);

        return $stmt->fetchAll();
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
