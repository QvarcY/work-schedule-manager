<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

require __DIR__ . '/autoload.php';
require __DIR__ . '/helpers.php';

App\Core\Env::load(dirname(__DIR__) . '/.env');
App\Core\Session::start();
App\Core\Translator::boot();
