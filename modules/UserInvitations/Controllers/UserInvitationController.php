<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\UserInvitations\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Services\ActivityLogger;
use PDO;

final class UserInvitationController
{
    private const VIEW_MODES = ['full', 'own', 'day', 'night'];

    public function adminIndex(): void
    {
        $admin = auth()->requireAdmin();
        $this->ensureUserColumns();
        ActivityLogger::log('user_invitations_viewed', 'user_invitation', null, 'Admins apskatīja ielūgumus.', $admin);

        view('user-invitations/admin', [
            'title' => 'Ielūgumi',
            'invitations' => $this->invitations(),
            'roles' => $this->roles(),
            'viewModes' => $this->viewModes(),
        ]);
    }

    public function create(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);
        $this->ensureUserColumns();

        $role = $this->roleFromRequest();
        $viewMode = $this->viewModeFromRequest();
        $contact = trim((string) ($_POST['invited_contact'] ?? ''));
        $days = max(1, min(30, (int) ($_POST['valid_days'] ?? 7)));
        $canBeScheduled = isset($_POST['can_be_scheduled']) ? 1 : 0;
        $token = bin2hex(random_bytes(32));

        $stmt = $this->db()->prepare(
            'INSERT INTO user_invitations
                (token_hash, invited_contact, intended_role, can_be_scheduled, schedule_view_mode, created_by, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))'
        );
        $stmt->execute([
            hash('sha256', $token),
            $contact !== '' ? $contact : null,
            $role,
            $canBeScheduled,
            $viewMode,
            (int) $admin['id'],
            $days,
        ]);

        Session::flash('success', 'Ielūguma saite izveidota: ' . url('/invite?token=' . $token));
        ActivityLogger::log('user_invitation_created', 'user_invitation', (int) $this->db()->lastInsertId(), $role, $admin);
        redirect('/user-invitations/admin');
    }

    public function showInvite(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $invitation = $this->invitationByToken($token);

        view('user-invitations/accept', [
            'title' => 'Ielūgums',
            'token' => $token,
            'invitation' => $invitation,
            'isAvailable' => $this->isAvailable($invitation),
        ]);
    }

    public function submitted(): void
    {
        view('user-invitations/submitted', [
            'title' => 'Pieteikums nosūtīts',
        ]);
    }

    public function submitInvite(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);

        $token = trim((string) ($_POST['token'] ?? ''));
        $invitation = $this->invitationByToken($token);
        if (!$this->isAvailable($invitation)) {
            Session::flash('error', 'Ielūguma saite nav derīga vai vairs nav aktīva.');
            redirect('/invite?token=' . rawurlencode($token));
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $username = $email;
        $password = (string) ($_POST['password'] ?? '');

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            Session::flash('error', 'Vārds, uzvārds, e-pasts un parole ir obligāti.');
            redirect('/invite?token=' . rawurlencode($token));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'E-pasta adrese nav korekta.');
            redirect('/invite?token=' . rawurlencode($token));
        }

        if ($this->passwordScore($password) < 3) {
            Session::flash('error', 'Parole ir par vāju. Izmanto garāku paroli ar burtiem, cipariem un simboliem.');
            redirect('/invite?token=' . rawurlencode($token));
        }

        if ($this->usernameExists($username)) {
            Session::flash('error', 'Šāds lietotājvārds jau eksistē.');
            redirect('/invite?token=' . rawurlencode($token));
        }

        $stmt = $this->db()->prepare(
            "UPDATE user_invitations
             SET status = 'submitted',
                 first_name = ?,
                 last_name = ?,
                 email = ?,
                 phone = ?,
                 username = ?,
                 password_hash = ?,
                 submitted_at = NOW()
             WHERE id = ? AND status = 'open'"
        );
        $stmt->execute([
            $firstName,
            $lastName,
            $email !== '' ? $email : null,
            $phone !== '' ? $phone : null,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            (int) $invitation['id'],
        ]);

        ActivityLogger::log('user_invitation_submitted', 'user_invitation', (int) $invitation['id'], $username, ['username' => $username]);
        redirect('/invite/submitted');
    }

    public function approve(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);
        $this->ensureUserColumns();

        $id = (int) ($_POST['id'] ?? 0);
        $role = $this->roleFromRequest();
        $viewMode = $this->viewModeFromRequest();
        $canBeScheduled = isset($_POST['can_be_scheduled']) ? 1 : 0;
        $invitation = $this->invitationById($id);

        if (!$invitation || ($invitation['status'] ?? '') !== 'submitted') {
            Session::flash('error', 'Apstiprināšanai nav derīga pieteikuma.');
            redirect('/user-invitations/admin');
        }

        $username = (string) ($invitation['username'] ?? '');
        if ($this->usernameExists($username)) {
            Session::flash('error', 'Lietotājvārds jau eksistē. Noraidi šo pieteikumu un izveido jaunu ielūgumu.');
            redirect('/user-invitations/admin');
        }

        $this->createApprovedUser($invitation, $role, $canBeScheduled, $viewMode);
        $newUserId = (int) $this->db()->lastInsertId();

        $stmt = $this->db()->prepare(
            "UPDATE user_invitations
             SET status = 'approved', approved_by = ?, decided_at = NOW(), intended_role = ?, can_be_scheduled = ?, schedule_view_mode = ?
             WHERE id = ?"
        );
        $stmt->execute([(int) $admin['id'], $role, $canBeScheduled, $viewMode, $id]);

        $this->notifyUser(
            $newUserId,
            'Konts apstiprināts',
            'Tavs konts ir apstiprināts. Tagad vari pieslēgties ar savu e-pastu un izveidoto paroli.',
            'account',
            (int) $admin['id']
        );

        ActivityLogger::log('user_invitation_approved', 'user_invitation', $id, $username . ' / ' . $role, $admin);
        Session::flash('success', 'Lietotājs apstiprināts un konts izveidots.');
        redirect('/user-invitations/admin');
    }

    public function reject(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $note = trim((string) ($_POST['admin_note'] ?? ''));

        $stmt = $this->db()->prepare(
            "UPDATE user_invitations
             SET status = 'rejected', admin_note = ?, approved_by = ?, decided_at = NOW()
             WHERE id = ? AND status IN ('submitted', 'open')"
        );
        $stmt->execute([$note !== '' ? $note : null, (int) $admin['id'], $id]);

        ActivityLogger::log('user_invitation_rejected', 'user_invitation', $id, $note, $admin);
        Session::flash('success', 'Ielūgums noraidīts.');
        redirect('/user-invitations/admin');
    }

    public function cancel(): void
    {
        $admin = auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $this->db()->prepare("UPDATE user_invitations SET status = 'cancelled', decided_at = NOW() WHERE id = ? AND status = 'open'");
        $stmt->execute([$id]);

        ActivityLogger::log('user_invitation_cancelled', 'user_invitation', $id, null, $admin);
        Session::flash('success', 'Ielūgums atcelts.');
        redirect('/user-invitations/admin');
    }

    private function createApprovedUser(array $invitation, string $role, int $canBeScheduled, string $viewMode): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO users
                (username, first_name, last_name, email, phone, password_hash, role, can_be_scheduled, schedule_view_mode)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $invitation['username'],
            $invitation['first_name'],
            $invitation['last_name'],
            $invitation['email'],
            $invitation['phone'],
            $invitation['password_hash'],
            $role,
            $canBeScheduled,
            $viewMode,
        ]);
    }

    private function notifyUser(int $userId, string $title, string $body, string $type, ?int $createdBy = null): void
    {
        if (!class_exists(\Modules\Notifications\NotificationService::class)) {
            return;
        }

        try {
            (new \Modules\Notifications\NotificationService())->createForUsers($title, $body, [$userId], $type, $createdBy);
        } catch (\Throwable) {
            // Notifications are helpful, but must not block account approval.
        }
    }

    private function invitations(): array
    {
        return $this->db()
            ->query('SELECT i.*, u.username AS created_by_username FROM user_invitations i LEFT JOIN users u ON u.id = i.created_by ORDER BY i.created_at DESC')
            ->fetchAll();
    }

    private function invitationByToken(string $token): ?array
    {
        if ($token === '' || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        $stmt = $this->db()->prepare('SELECT * FROM user_invitations WHERE token_hash = ? LIMIT 1');
        $stmt->execute([hash('sha256', $token)]);

        return $stmt->fetch() ?: null;
    }

    private function invitationById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM user_invitations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    private function isAvailable(?array $invitation): bool
    {
        if (!$invitation || ($invitation['status'] ?? '') !== 'open') {
            return false;
        }

        $expiresAt = (string) ($invitation['expires_at'] ?? '');
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            return false;
        }

        return true;
    }

    private function roles(): array
    {
        if (class_exists(\App\Services\AccessControl::class)) {
            try {
                return (new \App\Services\AccessControl())->roles();
            } catch (\Throwable) {
                // Fallback below keeps the module usable on older installs.
            }
        }

        return [
            ['code' => 'employee', 'label' => 'Darbinieks'],
            ['code' => 'viewer', 'label' => 'Skatītājs'],
            ['code' => 'control', 'label' => 'Kontrole'],
            ['code' => 'moderator', 'label' => 'Moderators'],
            ['code' => 'user', 'label' => 'Lietotājs'],
        ];
    }

    private function roleFromRequest(): string
    {
        $role = (string) ($_POST['role'] ?? $_POST['intended_role'] ?? 'employee');
        $allowed = array_column($this->roles(), 'code');

        return in_array($role, $allowed, true) ? $role : 'employee';
    }

    private function viewModes(): array
    {
        return [
            'full' => 'Pilnu grafiku',
            'own' => 'Tikai savas maiņas',
            'day' => 'Tikai dienas maiņas',
            'night' => 'Tikai nakts maiņas',
        ];
    }

    private function viewModeFromRequest(): string
    {
        $mode = (string) ($_POST['schedule_view_mode'] ?? 'own');

        return in_array($mode, self::VIEW_MODES, true) ? $mode : 'own';
    }

    private function usernameExists(string $username): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) AS count FROM users WHERE username = ?');
        $stmt->execute([$username]);

        return (int) ($stmt->fetch()['count'] ?? 0) > 0;
    }

    private function passwordScore(string $password): int
    {
        $score = 0;
        $score += strlen($password) >= 10 ? 1 : 0;
        $score += preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password) ? 1 : 0;
        $score += preg_match('/\d/', $password) ? 1 : 0;
        $score += preg_match('/[^A-Za-z0-9]/', $password) ? 1 : 0;

        return $score;
    }

    private function ensureUserColumns(): void
    {
        if (class_exists(\App\Services\AccessControl::class)) {
            (new \App\Services\AccessControl())->ensureSchema();
            return;
        }

        $this->ensureColumn('users', 'first_name', 'ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL AFTER username');
        $this->ensureColumn('users', 'last_name', 'ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name');
        $this->ensureColumn('users', 'email', 'ALTER TABLE users ADD COLUMN email VARCHAR(190) NULL AFTER last_name');
        $this->ensureColumn('users', 'phone', 'ALTER TABLE users ADD COLUMN phone VARCHAR(50) NULL AFTER email');
        $this->ensureColumn('users', 'can_be_scheduled', 'ALTER TABLE users ADD COLUMN can_be_scheduled TINYINT(1) NOT NULL DEFAULT 0 AFTER role');
        $this->ensureColumn('users', 'schedule_view_mode', "ALTER TABLE users ADD COLUMN schedule_view_mode ENUM('full', 'own', 'day', 'night') NOT NULL DEFAULT 'full' AFTER can_be_scheduled");

        try {
            $this->db()->exec("ALTER TABLE users MODIFY role ENUM('admin', 'moderator', 'control', 'employee', 'viewer', 'user') NOT NULL DEFAULT 'user'");
        } catch (\Throwable) {
            // Existing installs may already have a compatible role column.
        }
    }

    private function ensureColumn(string $table, string $column, string $sql): void
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) AS column_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        if ((int) ($stmt->fetch()['column_count'] ?? 0) === 0) {
            $this->db()->exec($sql);
        }
    }

    private function db(): PDO
    {
        return Database::connection();
    }
}
