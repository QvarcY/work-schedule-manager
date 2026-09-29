<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class App
{
    public static function run(Router $router): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = rtrim((string) parse_url((string) Env::get('APP_URL', ''), PHP_URL_PATH), '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
        }

        try {
            $router->dispatch($method, $uri);
        } catch (HttpException $exception) {
            http_response_code($exception->statusCode);
            $statusTitleKey = 'errors.status.' . $exception->statusCode . '.title';
            $errorTitle = Translator::has($statusTitleKey)
                ? t($statusTitleKey)
                : t('errors.title');
            View::render('errors/http', [
                'title' => $errorTitle,
                'errorTitle' => $errorTitle,
                'message' => $exception->getMessage(),
                'statusCode' => $exception->statusCode,
            ]);
        } catch (\Throwable $exception) {
            http_response_code(500);
            $debug = Env::bool('APP_DEBUG', false);
            View::render('errors/http', [
                'title' => t('errors.status.500.title'),
                'errorTitle' => t('errors.status.500.title'),
                'message' => $debug ? $exception->getMessage() : t('errors.generic'),
                'statusCode' => 500,
            ]);
        }
    }
}
