<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace Modules\Translations\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\Translator;

final class TranslationController
{
    private const MAX_UPLOAD_BYTES = 2_000_000;

    public function index(): void
    {
        auth()->requireAdmin();

        $fallbackLocale = Translator::fallbackLocale();
        $fallbackMessages = Translator::messages($fallbackLocale);
        $coverage = [];

        foreach (Translator::availableLocales() as $locale => $name) {
            $messages = Translator::messages($locale);
            $missing = array_values(array_diff(array_keys($fallbackMessages), array_keys($messages)));
            sort($missing);

            $coverage[] = [
                'locale' => $locale,
                'name' => $name,
                'translated' => count($fallbackMessages) - count($missing),
                'total' => count($fallbackMessages),
                'missing' => $missing,
                'has_override' => is_file($this->overridePath($locale)),
            ];
        }

        view('translations/index', [
            'title' => t('translations.title'),
            'coverage' => $coverage,
            'fallbackLocale' => $fallbackLocale,
        ]);
    }

    public function export(): never
    {
        auth()->requireAdmin();

        $locale = $this->normalizeLocale((string) ($_GET['locale'] ?? Translator::fallbackLocale()));
        if ($locale === null) {
            http_response_code(422);
            exit(t('language.invalid'));
        }

        $messages = Translator::messages($locale);
        if ($messages === []) {
            $messages = Translator::messages(Translator::fallbackLocale());
            $messages['language.name'] = strtoupper($locale);
            ksort($messages);
        }

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="translations-' . $locale . '.json"');
        echo json_encode(
            $messages,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
        exit;
    }

    public function import(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $locale = $this->normalizeLocale((string) ($_POST['locale'] ?? ''));
        $file = $_FILES['catalogue'] ?? [];

        if ($locale === null) {
            Session::flash('error', t('translations.import.invalid_locale'));
            redirect('/translations');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', t('translations.import.upload_failed'));
            redirect('/translations');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            Session::flash('error', t('translations.import.too_large'));
            redirect('/translations');
        }

        try {
            $contents = file_get_contents((string) $file['tmp_name']);
            if ($contents === false || strlen($contents) > self::MAX_UPLOAD_BYTES) {
                Session::flash('error', t('translations.import.too_large'));
                redirect('/translations');
            }
            $catalogue = json_decode((string) $contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            Session::flash('error', t('translations.import.invalid_json'));
            redirect('/translations');
        }

        if (!is_array($catalogue) || !isset($catalogue['language.name'])) {
            Session::flash('error', t('translations.import.missing_name'));
            redirect('/translations');
        }

        $knownKeys = Translator::messages(Translator::fallbackLocale());
        $normalized = [];
        foreach ($catalogue as $key => $value) {
            if (!is_string($key) || !is_string($value) || !array_key_exists($key, $knownKeys)) {
                continue;
            }
            $normalized[$key] = $value;
        }

        if ($normalized === [] || !isset($normalized['language.name'])) {
            Session::flash('error', t('translations.import.no_known_keys'));
            redirect('/translations');
        }

        ksort($normalized);

        try {
            $this->writeOverride($locale, $normalized);
        } catch (\Throwable) {
            Session::flash('error', t('translations.import.write_failed'));
            redirect('/translations');
        }

        Session::flash('success', t('translations.import.success', [
            'count' => count($normalized),
            'locale' => $locale,
        ]));
        redirect('/translations');
    }

    public function reset(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $locale = $this->normalizeLocale((string) ($_POST['locale'] ?? ''));
        if ($locale === null) {
            Session::flash('error', t('translations.import.invalid_locale'));
            redirect('/translations');
        }

        $path = $this->overridePath($locale);
        if (is_file($path)) {
            unlink($path);
        }

        Session::flash('success', t('translations.reset.success', ['locale' => $locale]));
        redirect('/translations');
    }

    private function writeOverride(string $locale, array $catalogue): void
    {
        $directory = dirname($this->overridePath($locale));
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new \RuntimeException('Unable to create language storage directory.');
        }

        $temporaryPath = $directory . '/.' . $locale . '-' . bin2hex(random_bytes(6)) . '.tmp';
        $json = json_encode(
            $catalogue,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        if (file_put_contents($temporaryPath, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write translation catalogue.');
        }

        if (!rename($temporaryPath, $this->overridePath($locale))) {
            unlink($temporaryPath);
            throw new \RuntimeException('Unable to publish translation catalogue.');
        }
    }

    private function overridePath(string $locale): string
    {
        return dirname(__DIR__, 3) . '/storage/lang/' . $locale . '.json';
    }

    private function normalizeLocale(string $locale): ?string
    {
        $locale = str_replace('_', '-', trim($locale));
        $parts = explode('-', $locale, 2);
        $normalized = strtolower($parts[0] ?? '');
        if (isset($parts[1])) {
            $normalized .= '-' . strtoupper($parts[1]);
        }

        return preg_match('/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $normalized) === 1
            ? $normalized
            : null;
    }
}
