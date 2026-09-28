<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

$publicRoot = dirname(__DIR__) . '/public';

$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

if (!is_string($path) || $path === '') {
    $path = '/';
}

$candidate = realpath(
    $publicRoot . $path
);

$publicReal = realpath($publicRoot);

if (
    $path !== '/'
    && $candidate !== false
    && $publicReal !== false
    && str_starts_with(
        $candidate,
        $publicReal . DIRECTORY_SEPARATOR
    )
    && is_file($candidate)
) {
    return false;
}

require $publicRoot . '/index.php';
