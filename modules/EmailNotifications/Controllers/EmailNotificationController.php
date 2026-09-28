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
            'title' => 'E-pasta paziņojumi',
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

        ActivityLogger::log('email_notifications_settings_updated', 'notification', null, 'E-pasta paziņojumu iestatījumi mainīti.', $admin);
        Session::flash('success', 'E-pasta paziņojumu iestatījumi saglabāti.');
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
            Session::flash('success', 'Testa e-pasts nosūtīts.');
        } else {
            Session::flash('error', 'Testa e-pastu neizdevās nosūtīt: ' . (string) $result['error']);
        }

        redirect('/email-notifications');
    }
}
