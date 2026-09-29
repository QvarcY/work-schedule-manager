<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class Translator
{
    private static string $locale = 'lv';
    private static string $fallbackLocale = 'lv';
    private static array $catalogues = [];

    public static function boot(): void
    {
        $availableLocales = self::availableLocales();
        $configuredFallback = self::normalizeLocale(
            (string) Env::get('APP_FALLBACK_LOCALE', 'lv')
        );
        self::$fallbackLocale = $configuredFallback !== null && isset($availableLocales[$configuredFallback])
            ? $configuredFallback
            : (isset($availableLocales['lv']) ? 'lv' : (array_key_first($availableLocales) ?? 'lv'));

        $defaultLocale = self::normalizeLocale(
            (string) Env::get('APP_LOCALE', self::$fallbackLocale)
        ) ?? self::$fallbackLocale;

        if (!isset($availableLocales[$defaultLocale])) {
            $defaultLocale = self::$fallbackLocale;
        }

        $requestedLocale = self::normalizeLocale(
            (string) Session::get('locale', $defaultLocale)
        ) ?? $defaultLocale;

        if (!self::setLocale($requestedLocale, false)) {
            self::setLocale(self::$fallbackLocale, false);
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function fallbackLocale(): string
    {
        return self::$fallbackLocale;
    }

    public static function setLocale(string $locale, bool $persist = true): bool
    {
        $locale = self::normalizeLocale($locale) ?? '';

        if ($locale === '' || !array_key_exists($locale, self::availableLocales())) {
            return false;
        }

        self::$locale = $locale;

        if ($persist) {
            Session::put('locale', $locale);
        }

        return true;
    }

    public static function translate(string $key, array $replace = [], ?string $default = null): string
    {
        $catalogue = self::catalogue(self::$locale);
        $fallbackCatalogue = self::catalogue(self::$fallbackLocale);
        $translation = $catalogue[$key]
            ?? $fallbackCatalogue[$key]
            ?? $default
            ?? $key;

        if (!is_string($translation)) {
            return $default ?? $key;
        }

        if ($replace === []) {
            return $translation;
        }

        $parameters = [];
        foreach ($replace as $name => $value) {
            if (is_scalar($value) || $value === null) {
                $parameters['{' . $name . '}'] = (string) $value;
            }
        }

        return strtr($translation, $parameters);
    }

    public static function has(string $key, ?string $locale = null): bool
    {
        $locale = self::normalizeLocale($locale ?? self::$locale) ?? self::$locale;

        return array_key_exists($key, self::catalogue($locale));
    }

    public static function availableLocales(): array
    {
        $locales = [];

        $paths = array_merge(
            glob(self::rootPath() . '/lang/*.json') ?: [],
            glob(self::rootPath() . '/storage/lang/*.json') ?: []
        );

        foreach ($paths as $path) {
            $locale = self::normalizeLocale((string) pathinfo($path, PATHINFO_FILENAME));
            if ($locale === null) {
                continue;
            }

            $catalogue = self::catalogue($locale);
            $locales[$locale] = (string) ($catalogue['language.name'] ?? strtoupper($locale));
        }

        ksort($locales);

        return $locales;
    }

    public static function messages(?string $locale = null): array
    {
        $locale = self::normalizeLocale($locale ?? self::$locale) ?? self::$locale;

        return self::catalogue($locale);
    }

    private static function catalogue(string $locale): array
    {
        if (isset(self::$catalogues[$locale])) {
            return self::$catalogues[$locale];
        }

        $catalogue = self::readCatalogue(self::rootPath() . '/lang/' . $locale . '.json');

        foreach (glob(self::rootPath() . '/modules/*/lang/' . $locale . '.json') ?: [] as $moduleCatalogue) {
            $catalogue = array_replace($catalogue, self::readCatalogue($moduleCatalogue));
        }

        $overridePath = self::rootPath() . '/storage/lang/' . $locale . '.json';
        if (is_file($overridePath)) {
            $catalogue = array_replace($catalogue, self::readCatalogue($overridePath));
        }

        self::$catalogues[$locale] = $catalogue;

        return $catalogue;
    }

    private static function readCatalogue(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }

        $catalogue = json_decode($contents, true);
        if (!is_array($catalogue)) {
            return [];
        }

        return array_filter(
            $catalogue,
            static fn (mixed $value): bool => is_string($value)
        );
    }

    private static function normalizeLocale(string $locale): ?string
    {
        $locale = str_replace('_', '-', trim($locale));
        if ($locale === '') {
            return null;
        }

        $parts = explode('-', $locale, 2);
        $normalized = strtolower($parts[0]);
        if (isset($parts[1])) {
            $normalized .= '-' . strtoupper($parts[1]);
        }

        return preg_match('/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $normalized) === 1
            ? $normalized
            : null;
    }

    private static function rootPath(): string
    {
        return dirname(__DIR__, 2);
    }
}
