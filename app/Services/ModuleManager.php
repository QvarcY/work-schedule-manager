<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Router;
use App\Core\Translator;
use PDO;

final class ModuleManager
{
    private string $modulePath;
    private string $storagePath;
    private ?PDO $db = null;

    public function __construct()
    {
        $root = dirname(__DIR__, 2);
        $this->modulePath = $root . '/modules';
        $this->storagePath = $root . '/storage';
    }

    public function availableModules(): array
    {
        $modules = [];

        foreach (glob($this->modulePath . '/*/module.json') ?: [] as $manifestPath) {
            $manifest = $this->readManifest($manifestPath);
            $modules[$manifest['name']] = $manifest;
        }

        ksort($modules);

        return $modules;
    }

    public function installedModules(): array
    {
        $this->ensureModuleTable();
        $stmt = $this->db()->query('SELECT * FROM modules ORDER BY installed_at DESC');
        $modules = [];

        foreach ($stmt->fetchAll() as $module) {
            $modules[$module['name']] = $module;
        }

        return $modules;
    }

    public function install(string $moduleName): void
    {
        if ($moduleName === '' || preg_match('/^[A-Za-z0-9_-]+$/', $moduleName) !== 1) {
            throw new HttpException(422, t('modules.errors.invalid_name'));
        }

        $manifestPath = $this->modulePath . '/' . $moduleName . '/module.json';
        if (!is_file($manifestPath)) {
            throw new HttpException(404, t('modules.errors.not_found'));
        }

        $manifest = $this->readManifest($manifestPath);
        $installed = $this->installedModules();

        if (isset($installed[$manifest['name']])) {
            throw new HttpException(409, t('modules.errors.already_installed'));
        }

        $this->ensureModuleTable();

        foreach ($manifest['migrations'] ?? [] as $migration) {
            $this->runMigration(dirname($manifestPath) . '/' . $migration);
        }

        $stmt = $this->db()->prepare(
            'INSERT INTO modules (name, title, version, description, active, source, installed_at) VALUES (?, ?, ?, ?, 1, ?, NOW())'
        );
        $stmt->execute([
            $manifest['name'],
            $manifest['title'] ?? $manifest['name'],
            $manifest['version'] ?? '1.0.0',
            $manifest['description'] ?? '',
            $moduleName,
        ]);
    }

    public function upload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new HttpException(422, t('modules.errors.upload_failed'));
        }

        if (!class_exists(\ZipArchive::class)) {
            throw new HttpException(500, t('modules.errors.zip_unavailable'));
        }

        $originalName = (string) ($file['name'] ?? '');
        if (!str_ends_with(strtolower($originalName), '.zip')) {
            throw new HttpException(422, t('modules.errors.zip_required'));
        }

        $tmpRoot = $this->storagePath . '/module_upload_' . bin2hex(random_bytes(8));
        if (!is_dir($tmpRoot) && !mkdir($tmpRoot, 0755, true)) {
            throw new HttpException(500, t('modules.errors.temp_directory'));
        }

        $zip = new \ZipArchive();
        if ($zip->open((string) $file['tmp_name']) !== true) {
            $this->removeDirectory($tmpRoot);
            throw new HttpException(422, t('modules.errors.zip_open'));
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $entry)) {
                $zip->close();
                $this->removeDirectory($tmpRoot);
                throw new HttpException(422, t('modules.errors.unsafe_path'));
            }
        }

        $zip->extractTo($tmpRoot);
        $zip->close();

        $manifestPath = $this->findUploadedManifest($tmpRoot);
        $manifest = $this->readManifest($manifestPath);
        $moduleName = (string) $manifest['name'];

        if (preg_match('/^[A-Za-z0-9_-]+$/', $moduleName) !== 1) {
            $this->removeDirectory($tmpRoot);
            throw new HttpException(422, t('modules.errors.invalid_archive_name'));
        }

        if (!is_dir($this->modulePath) && !mkdir($this->modulePath, 0755, true)) {
            $this->removeDirectory($tmpRoot);
            throw new HttpException(500, t('modules.errors.directory_create'));
        }

        $target = $this->modulePath . '/' . $moduleName;
        $moduleRoot = dirname($manifestPath);
        $backup = null;
        if (is_dir($target)) {
            $backup = $this->storagePath . '/module_backup_' . $moduleName . '_' . bin2hex(random_bytes(6));
            if (!rename($target, $backup)) {
                $this->removeDirectory($tmpRoot);
                throw new HttpException(500, t('modules.errors.update_prepare'));
            }
        }

        if (!rename($moduleRoot, $target)) {
            if ($backup !== null && is_dir($backup)) {
                rename($backup, $target);
            }
            $this->removeDirectory($tmpRoot);
            throw new HttpException(500, t('modules.errors.move_failed'));
        }

        $this->removeDirectory($tmpRoot);
        if ($backup !== null) {
            $this->removeDirectory($backup);
            $this->updateInstalledModule($manifest, $moduleName);
        }

        return $moduleName;
    }

    public function setActive(string $moduleName, bool $active): void
    {
        $this->ensureModuleTable();
        $stmt = $this->db()->prepare('UPDATE modules SET active = ? WHERE name = ?');
        $stmt->execute([$active ? 1 : 0, $moduleName]);
    }

    public function uninstall(string $moduleName, bool $deleteFiles = false): void
    {
        $this->ensureModuleTable();

        if ($moduleName === '' || preg_match('/^[A-Za-z0-9_-]+$/', $moduleName) !== 1) {
            throw new HttpException(422, t('modules.errors.invalid_name'));
        }

        $manifestPath = $this->modulePath . '/' . $moduleName . '/module.json';
        if (is_file($manifestPath)) {
            $manifest = $this->readManifest($manifestPath);
            foreach ($manifest['uninstall_migrations'] ?? [] as $migration) {
                $this->runMigration(dirname($manifestPath) . '/' . $migration);
            }
        }

        $stmt = $this->db()->prepare('DELETE FROM modules WHERE name = ?');
        $stmt->execute([$moduleName]);

        if ($deleteFiles) {
            $this->removeDirectory($this->modulePath . '/' . $moduleName);
        }
    }

    public function registerInstalledRoutes(Router $router): void
    {
        try {
            $installed = $this->installedModules();
        } catch (\Throwable) {
            return;
        }

        foreach ($installed as $moduleName => $module) {
            if ((int) ($module['active'] ?? 1) !== 1) {
                continue;
            }

            if (preg_match('/^[A-Za-z0-9_-]+$/', $moduleName) !== 1) {
                continue;
            }

            $routesFile = $this->modulePath . '/' . $moduleName . '/routes.php';
            if (is_file($routesFile)) {
                require $routesFile;
            }
        }
    }

    public function activeNavigation(?string $role, ?array $user = null): array
    {
        $items = [];

        try {
            $installed = $this->installedModules();
        } catch (\Throwable) {
            return [];
        }

        foreach ($installed as $moduleName => $module) {
            if ((int) ($module['active'] ?? 1) !== 1) {
                continue;
            }

            if (preg_match('/^[A-Za-z0-9_-]+$/', $moduleName) !== 1) {
                continue;
            }

            $manifestPath = $this->modulePath . '/' . $moduleName . '/module.json';
            if (!is_file($manifestPath)) {
                continue;
            }

            $manifest = $this->readManifest($manifestPath);
            $badges = $this->navigationBadges($moduleName, $user);
            foreach ($manifest['navigation'] ?? [] as $item) {
                $roles = $item['roles'] ?? [];
                if (!empty($roles) && !in_array($role, $roles, true)) {
                    continue;
                }

                if (empty($item['label']) || empty($item['path'])) {
                    continue;
                }

                $items[] = [
                    'label' => (string) $item['label'],
                    'path' => (string) $item['path'],
                    'module' => $moduleName,
                    'badge' => $badges[(string) $item['path']] ?? null,
                ];
            }
        }

        return $items;
    }

    private function updateInstalledModule(array $manifest, string $source): void
    {
        try {
            $installed = $this->installedModules();
            if (!isset($installed[$manifest['name']])) {
                return;
            }

            foreach ($manifest['migrations'] ?? [] as $migration) {
                $this->runMigration($this->modulePath . '/' . $manifest['name'] . '/' . $migration);
            }

            $stmt = $this->db()->prepare(
                'UPDATE modules SET title = ?, version = ?, description = ?, source = ?, updated_at = NOW() WHERE name = ?'
            );
            $stmt->execute([
                $manifest['title'] ?? $manifest['name'],
                $manifest['version'] ?? '1.0.0',
                $manifest['description'] ?? '',
                $source,
                $manifest['name'],
            ]);
        } catch (\Throwable) {
            throw new HttpException(500, t('modules.errors.update_failed'));
        }
    }

    private function navigationBadges(string $moduleName, ?array $user): array
    {
        $badgeFile = $this->modulePath . '/' . $moduleName . '/navigation_badges.php';
        if (!is_file($badgeFile)) {
            return [];
        }

        try {
            $badges = require $badgeFile;
        } catch (\Throwable) {
            return [];
        }

        if (is_callable($badges)) {
            try {
                $badges = $badges($user);
            } catch (\Throwable) {
                return [];
            }
        }

        if (!is_array($badges)) {
            return [];
        }

        $normalized = [];
        foreach ($badges as $path => $badge) {
            if (is_numeric($badge)) {
                $count = max(0, (int) $badge);
                $normalized[(string) $path] = [
                    'count' => $count,
                    'tone' => $count > 0 ? 'danger' : 'info',
                ];
                continue;
            }

            if (!is_array($badge)) {
                continue;
            }

            $count = max(0, (int) ($badge['count'] ?? 0));
            $tone = (string) ($badge['tone'] ?? ($count > 0 ? 'danger' : 'info'));
            $normalized[(string) $path] = [
                'count' => $count,
                'tone' => in_array($tone, ['info', 'danger'], true) ? $tone : 'info',
            ];
        }

        return $normalized;
    }

    private function readManifest(string $path): array
    {
        $manifest = json_decode((string) file_get_contents($path), true);

        if (!is_array($manifest) || empty($manifest['name'])) {
            throw new HttpException(422, t('modules.errors.invalid_manifest'));
        }

        $translationPrefix = 'modules.' . $manifest['name'];
        $manifest['title'] = Translator::translate(
            $translationPrefix . '.title',
            [],
            (string) ($manifest['title'] ?? $manifest['name'])
        );
        $manifest['description'] = Translator::translate(
            $translationPrefix . '.description',
            [],
            (string) ($manifest['description'] ?? '')
        );

        foreach ($manifest['navigation'] ?? [] as $index => $item) {
            $manifest['navigation'][$index]['label'] = Translator::translate(
                $translationPrefix . '.navigation.' . $index,
                [],
                (string) ($item['label'] ?? '')
            );
        }

        return $manifest;
    }

    private function runMigration(string $path): void
    {
        if (!is_file($path)) {
            throw new HttpException(
                422,
                t('modules.errors.migration_missing', ['file' => basename($path)])
            );
        }

        try {
            MigrationRunner::runFile(
                $this->db(),
                $path
            );
        } catch (\Throwable) {
            throw new HttpException(
                500,
                t('modules.errors.migration_failed', ['file' => basename($path)])
            );
        }
    }
    private function ensureModuleTable(): void
    {
        $this->db()->exec(
            "CREATE TABLE IF NOT EXISTS modules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                version VARCHAR(50) NOT NULL,
                description TEXT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                source VARCHAR(255) NULL,
                installed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->ensureColumn('modules', 'active', "ALTER TABLE modules ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
        $this->ensureColumn('modules', 'source', "ALTER TABLE modules ADD COLUMN source VARCHAR(255) NULL");
        $this->ensureColumn('modules', 'updated_at', "ALTER TABLE modules ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    private function ensureColumn(string $table, string $column, string $sql): void
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) AS column_count
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();

        if ((int) ($result['column_count'] ?? 0) === 0) {
            $this->db()->exec($sql);
        }
    }

    private function findUploadedManifest(string $root): string
    {
        $direct = $root . '/module.json';
        if (is_file($direct)) {
            return $direct;
        }

        $matches = glob($root . '/*/module.json') ?: [];
        if (count($matches) !== 1) {
            $this->removeDirectory($root);
            throw new HttpException(422, t('modules.errors.single_manifest'));
        }

        return $matches[0];
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $root = realpath($path);
        $allowed = realpath($this->storagePath) ?: $this->storagePath;
        $modules = realpath($this->modulePath) ?: $this->modulePath;

        if ($root === false || (!str_starts_with($root, $allowed) && !str_starts_with($root, $modules))) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($root);
    }

    private function db(): PDO
    {
        if (!$this->db instanceof PDO) {
            $this->db = Database::connection();
        }

        return $this->db;
    }
}
