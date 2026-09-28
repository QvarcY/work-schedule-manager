<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');

        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }

        return $token;
    }

    public static function verify(?string $token): void
    {
        if (!$token || !hash_equals((string) Session::get('_csrf'), $token)) {
            throw new HttpException(419, 'Drošības tokena pārbaude neizdevās.');
        }
    }
}
