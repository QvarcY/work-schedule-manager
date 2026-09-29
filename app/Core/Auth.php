<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Services\AccessControl;

final class Auth
{
    public function user(): ?array
    {
        $id = Session::get('user_id');

        if (!$id) {
            return null;
        }

        return (new User())->findById((int) $id);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function attempt(string $username, string $password): bool
    {
        $user = (new User())->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);

        if (!empty($user['locale'])) {
            Translator::setLocale((string) $user['locale']);
        }

        return true;
    }

    public function logout(): void
    {
        Session::forget('user_id');
        Session::regenerate();
    }

    public function requireLogin(): array
    {
        $user = $this->user();

        if (!$user) {
            redirect('/login');
        }

        return $user;
    }

    public function requireAdmin(): array
    {
        $user = $this->requireLogin();

        if (($user['role'] ?? '') !== 'admin') {
            throw new HttpException(403, t('security.admin_required'));
        }

        return $user;
    }

    public function can(string $permission): bool
    {
        $user = $this->user();

        return $user ? (new AccessControl())->userCan($user, $permission) : false;
    }
}
