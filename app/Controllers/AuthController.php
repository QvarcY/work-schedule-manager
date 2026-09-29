<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Services\ActivityLogger;

final class AuthController
{
    public function showLogin(): void
    {
        view('auth/login', [
            'title' => t('auth.login.title'),
        ]);
    }

    public function about(): void
    {
        view('auth/about', [
            'title' => t('about.title'),
        ]);
    }

    public function login(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!auth()->attempt($username, $password)) {
            ActivityLogger::log('login_failed', 'user', null, 'username=' . $username, ['username' => $username]);
            Session::flash('error', t('auth.login.failed'));
            redirect('/login');
        }

        ActivityLogger::log('login_success', 'user', null, 'login successful');
        redirect('/');
    }

    public function logout(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);
        ActivityLogger::log('logout', 'user', null, 'logout');
        auth()->logout();
        redirect('/login');
    }
}
