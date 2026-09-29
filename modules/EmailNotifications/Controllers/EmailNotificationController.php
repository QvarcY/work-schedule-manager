<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\EmailNotifications\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Services\ActivityLogger;
use Modules\EmailNotifications\EmailNotificationChannel;

final class EmailNotificationController
{
    public function index(): void
    {
        auth()->requireAdmin();
        $channel = new EmailNotificationChannel();

        view('email-notifications/index', [
            'title' => t('email_notifications.title'),
            'settings' => $channel->settings(),
            'recentDeliveries' => $channel->recentDeliveries(),
        ]);
    }

    public function saveSettings(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        (new EmailNotificationChannel())->saveSettings([
            'enabled' => isset($_POST['enabled']) ? '1' : '0',
            'from_email' => trim((string) ($_POST['from_email'] ?? '')),
            'from_name' => trim((string) ($_POST['from_name'] ?? '')),
            'reply_to' => trim((string) ($_POST['reply_to'] ?? '')),
            'subject_prefix' => trim((string) ($_POST['subject_prefix'] ?? '')),
        ]);

        ActivityLogger::log('email_notifications_settings_updated', 'notification', null, 'email notification settings updated', $admin);
        Session::flash('success', t('email_notifications.settings.success'));
        redirect('/email-notifications');
    }

    public function sendTest(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $email = trim((string) ($_POST['test_email'] ?? ($admin['email'] ?? '')));
        $result = (new EmailNotificationChannel())->sendTestEmail($email);

        if ($result['sent']) {
            ActivityLogger::log('email_notifications_test_sent', 'notification', null, $email, $admin);
            Session::flash('success', t('email_notifications.test.success'));
        } else {
            Session::flash('error', t('email_notifications.test.failed', ['error' => (string) $result['error']]));
        }

        redirect('/email-notifications');
    }
}
