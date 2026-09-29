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
$catalogueDirectories = array_merge(
    [$root . '/lang'],
    glob($root . '/modules/*/lang', GLOB_ONLYDIR) ?: []
);
$failed = false;
$fileCount = 0;
$cataloguesByLocale = [];

$relativePath = static function (string $path) use ($root): string {
    $normalizedRoot = str_replace('\\', '/', $root);
    $normalizedPath = str_replace('\\', '/', $path);

    return str_starts_with($normalizedPath, $normalizedRoot . '/')
        ? substr($normalizedPath, strlen($normalizedRoot) + 1)
        : $normalizedPath;
};

$placeholders = static function (string $message): array {
    preg_match_all('/\{([A-Za-z0-9_.-]+)\}/', $message, $matches);
    $names = array_values(array_unique($matches[1] ?? []));
    sort($names);

    return $names;
};

foreach ($catalogueDirectories as $directory) {
    $files = glob($directory . '/*.json') ?: [];
    sort($files);

    if ($files === []) {
        continue;
    }

    $directoryCatalogues = [];
    foreach ($files as $file) {
        $locale = (string) pathinfo($file, PATHINFO_FILENAME);

        try {
            $contents = file_get_contents($file);
            if ($contents === false) {
                throw new RuntimeException('Unable to read file.');
            }

            $catalogue = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($catalogue)) {
                throw new RuntimeException('Catalogue root must be a JSON object.');
            }

            foreach ($catalogue as $key => $message) {
                if (!is_string($key) || $key === '' || !is_string($message)) {
                    throw new RuntimeException('Every translation key and value must be a string.');
                }
            }

            $directoryCatalogues[$locale] = $catalogue;
            $cataloguesByLocale[$locale][$file] = $catalogue;
            $fileCount++;
            echo sprintf("OK  %-72s %d key(s)\n", $relativePath($file), count($catalogue));
        } catch (Throwable $exception) {
            $failed = true;
            fwrite(STDERR, 'FAIL ' . $relativePath($file) . ' — ' . $exception->getMessage() . PHP_EOL);
        }
    }

    if ($directoryCatalogues === []) {
        continue;
    }

    $referenceLocale = isset($directoryCatalogues['lv']) ? 'lv' : array_key_first($directoryCatalogues);
    $reference = $directoryCatalogues[$referenceLocale];

    foreach ($directoryCatalogues as $locale => $catalogue) {
        if ($locale === $referenceLocale) {
            continue;
        }

        $missing = array_diff_key($reference, $catalogue);
        $extra = array_diff_key($catalogue, $reference);
        if ($missing !== [] || $extra !== []) {
            $failed = true;
            $label = $relativePath($directory) . '/' . $locale . '.json';
            foreach (array_keys($missing) as $key) {
                fwrite(STDERR, "FAIL {$label} — missing key: {$key}\n");
            }
            foreach (array_keys($extra) as $key) {
                fwrite(STDERR, "FAIL {$label} — unexpected key: {$key}\n");
            }
        }

        foreach (array_intersect_key($reference, $catalogue) as $key => $referenceMessage) {
            $expected = $placeholders($referenceMessage);
            $actual = $placeholders($catalogue[$key]);
            if ($expected !== $actual) {
                $failed = true;
                fwrite(
                    STDERR,
                    'FAIL ' . $relativePath($directory) . '/' . $locale . '.json'
                    . ' — placeholder mismatch for ' . $key
                    . ': expected [' . implode(', ', $expected) . ']'
                    . ', got [' . implode(', ', $actual) . ']' . PHP_EOL
                );
            }
        }
    }
}

foreach ($cataloguesByLocale as $locale => $files) {
    $owners = [];
    foreach ($files as $file => $catalogue) {
        foreach (array_keys($catalogue) as $key) {
            if (isset($owners[$key])) {
                $failed = true;
                fwrite(
                    STDERR,
                    'FAIL duplicate key ' . $key . ' for ' . $locale
                    . ': ' . $relativePath($owners[$key])
                    . ' and ' . $relativePath($file) . PHP_EOL
                );
            } else {
                $owners[$key] = $file;
            }
        }
    }
}

$fallbackKeys = [];
foreach ($cataloguesByLocale['lv'] ?? [] as $catalogue) {
    $fallbackKeys += $catalogue;
}

$sourceFiles = [];
foreach ([$root . '/app', $root . '/modules', $root . '/public'] as $sourceDirectory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $sourceFiles[] = $file->getPathname();
        }
    }
}

foreach ($sourceFiles as $sourceFile) {
    $source = file_get_contents($sourceFile);
    if ($source === false) {
        continue;
    }

    preg_match_all('/\bt\(\s*[\'\"]([^\'\"]+)[\'\"]\s*[,)]/', $source, $matches);
    foreach (array_unique($matches[1] ?? []) as $key) {
        if (!array_key_exists($key, $fallbackKeys)) {
            $failed = true;
            fwrite(
                STDERR,
                'FAIL ' . $relativePath($sourceFile)
                . ' — translation key missing from lv catalogues: ' . $key . PHP_EOL
            );
        }
    }
}

echo PHP_EOL;

if ($failed) {
    fwrite(STDERR, "Translation catalogue check FAILED.\n");
    exit(1);
}

echo sprintf("Translation catalogue check OK: %d file(s).\n", $fileCount);
