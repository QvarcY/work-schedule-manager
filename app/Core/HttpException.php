<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class HttpException extends \RuntimeException
{
    public function __construct(public int $statusCode, string $message)
    {
        parent::__construct($message);
    }
}
