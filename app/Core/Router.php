<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            throw new HttpException(404, 'Lapa nav atrasta.');
        }

        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$class, $action] = $handler;
        (new $class())->$action();
    }

    private function add(string $method, string $path, array|callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }
}
