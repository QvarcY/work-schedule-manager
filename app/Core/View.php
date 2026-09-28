<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__) . '/Views/' . $template . '.php';

        if (!is_file($viewFile)) {
            foreach (glob(dirname(__DIR__, 2) . '/modules/*/Views/' . $template . '.php') ?: [] as $moduleView) {
                $viewFile = $moduleView;
                break;
            }
        }

        if (!is_file($viewFile)) {
            throw new HttpException(500, 'Skata fails nav atrasts: ' . $template);
        }

        require dirname(__DIR__) . '/Views/layout.php';
    }
}
