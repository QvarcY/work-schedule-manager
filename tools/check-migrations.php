<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool can only be run from CLI.\n");
    exit(1);
}

$root = dirname(__DIR__);

require $root . '/app/autoload.php';

use App\Services\MigrationRunner;

$files = array_merge(
    glob($root . '/database/migrations/*.sql') ?: [],
    glob($root . '/modules/*/migrations/*.sql') ?: []
);

sort($files);

if ($files === []) {
    fwrite(STDERR, "No migration files found.\n");
    exit(1);
}

$totalStatements = 0;
$failed = false;

foreach ($files as $file) {
    try {
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException(
                'Unable to read file.'
            );
        }

        $statements = MigrationRunner::statements($sql);
        $count = count($statements);
        $totalStatements += $count;

        $relative = str_replace(
            $root . DIRECTORY_SEPARATOR,
            '',
            $file
        );

        echo sprintf(
            "OK  %-72s %d statement(s)\n",
            $relative,
            $count
        );
    } catch (Throwable $exception) {
        $failed = true;

        fwrite(
            STDERR,
            'FAIL '
            . $file
            . ' — '
            . $exception->getMessage()
            . PHP_EOL
        );
    }
}

echo PHP_EOL;

if ($failed) {
    fwrite(STDERR, "Migration parser check FAILED.\n");
    exit(1);
}

echo sprintf(
    "Migration parser check OK: %d file(s), %d statement(s).\n",
    count($files),
    $totalStatements
);
