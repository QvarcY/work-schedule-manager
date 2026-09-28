<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public static function runFile(PDO $pdo, string $path): int
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                'Migration file is not readable: ' . $path
            );
        }

        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException(
                'Unable to read migration file: ' . $path
            );
        }

        $statements = self::statements($sql);

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        return count($statements);
    }

    public static function statements(string $sql): array
    {
        $statements = [];
        $buffer = '';

        $length = strlen($sql);
        $quote = null;
        $lineComment = false;
        $blockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length
                ? $sql[$i + 1]
                : null;

            if ($lineComment) {
                if ($char === "\n") {
                    $lineComment = false;
                    $buffer .= "\n";
                }

                continue;
            }

            if ($blockComment) {
                if ($char === '*' && $next === '/') {
                    $blockComment = false;
                    $i++;
                    $buffer .= ' ';
                }

                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;

                if ($char === '\\' && $next !== null) {
                    $buffer .= $next;
                    $i++;
                    continue;
                }

                if ($char === $quote) {
                    if ($next === $quote) {
                        $buffer .= $next;
                        $i++;
                        continue;
                    }

                    $quote = null;
                }

                continue;
            }

            if (
                $char === "'"
                || $char === '"'
                || $char === '`'
            ) {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if (
                $char === '-'
                && $next === '-'
            ) {
                $after = $i + 2 < $length
                    ? $sql[$i + 2]
                    : null;

                if ($after === null || ctype_space($after)) {
                    $lineComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === '#') {
                $lineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $blockComment = true;
                $i++;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if ($quote !== null) {
            throw new RuntimeException(
                'Unterminated quoted string in SQL migration.'
            );
        }

        if ($blockComment) {
            throw new RuntimeException(
                'Unterminated block comment in SQL migration.'
            );
        }

        $statement = trim($buffer);

        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}
