<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $sessionName = trim((string) Env::get(
            'SESSION_NAME',
            'work_schedule_manager_session'
        ));

        if ($sessionName !== '') {
            session_name($sessionName);
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => self::shouldUseSecureCookie(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function pullFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $message;
    }

    private static function shouldUseSecureCookie(): bool
    {
        $override = Env::get('SESSION_SECURE_COOKIE');

        if (is_bool($override)) {
            return $override;
        }

        if (is_string($override) && trim($override) !== '') {
            return filter_var(
                $override,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $appUrl = trim((string) Env::get('APP_URL', ''));

        if ($appUrl !== '') {
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);

            if (is_string($scheme) && $scheme !== '') {
                return strtolower($scheme) === 'https';
            }
        }

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));

        return $https !== ''
            && $https !== 'off'
            && $https !== '0';
    }
}
