<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This test can only be run from CLI.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Translator;
use App\Services\ModuleManager;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . PHP_EOL
            . 'Expected: ' . var_export($expected, true) . PHP_EOL
            . 'Actual:   ' . var_export($actual, true)
        );
    }
};

$locales = Translator::availableLocales();
$assertSame('Latviešu', $locales['lv'] ?? null, 'Latvian must be available.');
$assertSame('English', $locales['en'] ?? null, 'English must be available.');
$assertSame(true, isset($locales[Translator::fallbackLocale()]), 'The fallback locale must be available.');

$assertSame(true, Translator::setLocale('en', false), 'English locale must be selectable.');
$assertSame('Dashboard', t('dashboard.title'), 'Core English catalogue must be loaded.');
$assertSame(
    'Signed in as Anna with the employee role.',
    t('dashboard.signed_in', ['username' => 'Anna', 'role' => 'employee']),
    'Translation placeholders must be replaced.'
);
$assertSame(
    'Deleted activity entries: 3.',
    t('journal.prune.success', ['count' => 3]),
    'Dynamic core messages must be translated with their placeholders.'
);
$assertSame(
    'Enter a valid date.',
    t('day_off.validation.invalid_date'),
    'Module validation messages must be loaded from module catalogues.'
);

$modules = (new ModuleManager())->availableModules();
$assertSame(
    'Translation management',
    $modules['Translations']['title'] ?? null,
    'Module metadata must use the active locale.'
);

$assertSame(false, Translator::setLocale('zz', false), 'Unknown locale must be rejected.');
$assertSame('Dashboard', t('dashboard.title'), 'Rejecting an unknown locale must preserve the active locale.');
$assertSame('Explicit fallback', t('test.missing', [], 'Explicit fallback'), 'Explicit defaults must be supported.');

echo "Translator runtime test OK.\n";
