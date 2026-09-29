<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

use Modules\Translations\Controllers\TranslationController;

$router->get('/translations', [TranslationController::class, 'index']);
$router->get('/translations/export', [TranslationController::class, 'export']);
$router->post('/translations/import', [TranslationController::class, 'import']);
$router->post('/translations/reset', [TranslationController::class, 'reset']);
