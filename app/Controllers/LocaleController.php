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
use App\Core\Database;
use App\Core\Session;
use App\Core\Translator;

final class LocaleController
{
    public function update(): void
    {
        Csrf::verify($_POST['_csrf'] ?? null);

        $locale = trim((string) ($_POST['locale'] ?? ''));
        $returnTo = $this->safeReturnPath((string) ($_POST['return_to'] ?? '/'));

        if (!Translator::setLocale($locale)) {
            Session::flash('error', t('language.invalid'));
            redirect($returnTo);
        }

        $user = auth()->user();
        if ($user) {
            try {
                $stmt = Database::connection()->prepare('UPDATE users SET locale = ? WHERE id = ?');
                $stmt->execute([Translator::locale(), (int) $user['id']]);
            } catch (\Throwable) {
                // The session preference remains usable before the locale migration is applied.
            }
        }

        Session::flash('success', t('language.changed'));
        redirect($returnTo);
    }

    private function safeReturnPath(string $path): string
    {
        $path = trim($path);

        if (
            $path === ''
            || !str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || str_contains($path, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
        ) {
            return '/';
        }

        return $path;
    }
}
