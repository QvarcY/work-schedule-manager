<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Translator;
use App\Core\View;

function env_value(string $key, mixed $default = null): mixed
{
    return App\Core\Env::get($key, $default);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path): string
{
    $base = rtrim((string) env_value('APP_URL', ''), '/');
    $path = '/' . ltrim($path, '/');

    return $base !== '' ? $base . $path : $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $template, array $data = []): void
{
    View::render($template, $data);
}

function t(string $key, array $replace = [], ?string $default = null): string
{
    return Translator::translate($key, $replace, $default);
}

function current_locale(): string
{
    return Translator::locale();
}

function available_locales(): array
{
    return Translator::availableLocales();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function auth(): Auth
{
    return new Auth();
}
