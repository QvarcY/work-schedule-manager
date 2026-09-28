<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

use App\Core\Session;
use App\Services\ModuleManager;

$currentUser = auth()->user();
$error = Session::pullFlash('error');
$success = Session::pullFlash('success');
$moduleNavigation = [];
$currentRole = $currentUser['role'] ?? null;
$brandName = trim((string) env_value('APP_NAME', 'Work Schedule Manager'));

if ($brandName === '') {
    $brandName = 'Work Schedule Manager';
}
$userLabel = $currentUser ? (string) ($currentUser['username'] ?? '') : '';
$notificationUnreadCount = 0;
$notificationsAvailable = false;
$moduleByPath = [];

if ($currentUser) {
    try {
        $stmt = \App\Core\Database::connection()->prepare(
            'SELECT COUNT(*) AS count
             FROM notification_recipients
             WHERE user_id = ?
               AND read_at IS NULL'
        );
        $stmt->execute([(int) $currentUser['id']]);
        $notificationUnreadCount = (int) ($stmt->fetch()['count'] ?? 0);
        $notificationsAvailable = true;
    } catch (Throwable) {
        $notificationUnreadCount = 0;
        $notificationsAvailable = false;
    }

    try {
        $moduleNavigation = (new ModuleManager())->activeNavigation($currentRole, $currentUser);
        $moduleNavigation = array_values(array_filter(
            $moduleNavigation,
            static fn (array $item): bool => ($item['path'] ?? '') !== '/notifications'
        ));
        foreach ($moduleNavigation as $item) {
            $moduleByPath[(string) ($item['path'] ?? '')] = $item;
        }
    } catch (Throwable) {
        $moduleNavigation = [];
    }
}

$mainLinks = [
    ['label' => 'Pārskats', 'path' => '/'],
];

if (isset($moduleByPath['/employee/profile'])) {
    $mainLinks[] = ['label' => 'Profils', 'path' => '/employee/profile'];
}

$mainLinks[] = ['label' => 'Grafiki', 'path' => '/schedules'];

$requestLinks = [];
foreach (['/day-off-requests' => 'Brīvdienas', '/schedule-acknowledgements' => 'Apliecinājumi'] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $requestLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$settingsLinks = [];
if ($notificationsAvailable) {
    $settingsLinks[] = ['label' => 'Paziņojumu iestatījumi', 'path' => '/notifications'];
}

$changeLinks = [];
if (($currentRole ?? '') === 'admin' && isset($moduleByPath['/schedule-changes/admin'])) {
    $changeLinks[] = ['label' => 'Izmaiņas', 'path' => '/schedule-changes/admin', 'badge' => $moduleByPath['/schedule-changes/admin']['badge'] ?? null];
} elseif (isset($moduleByPath['/schedule-changes'])) {
    $changeLinks[] = ['label' => 'Izmaiņas', 'path' => '/schedule-changes', 'badge' => $moduleByPath['/schedule-changes']['badge'] ?? null];
}

$adminScheduleLinks = [
    ['label' => 'Jauns grafiks', 'path' => '/schedules/create'],
    ['label' => 'Apzīmējumi', 'path' => '/shift-types'],
];

$adminPeopleLinks = [
    ['label' => 'Lietotāji', 'path' => '/users'],
];

foreach (['/employee-profiles/admin' => 'Darbinieki', '/user-invitations/admin' => 'Ielūgumi'] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $adminPeopleLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$adminRequestLinks = [];
foreach (['/day-off-requests/admin' => 'Brīvdienu pieteikumi', '/schedule-acknowledgements/admin' => 'Grafiku apliecinājumi'] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $adminRequestLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$adminSystemLinks = [
    ['label' => 'Moduļu pārvaldība', 'path' => '/modules'],
    ['label' => 'Darbību žurnāls', 'path' => '/journal'],
];

foreach (['/notifications/admin' => 'Paziņojumu pārvaldība', '/system-status' => 'Sistēmas statuss'] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $adminSystemLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$knownPaths = array_fill_keys(array_merge(
    ['/employee/profile', '/day-off-requests', '/schedule-acknowledgements', '/schedule-changes'],
    ['/employee-profiles/admin', '/user-invitations/admin', '/day-off-requests/admin', '/schedule-acknowledgements/admin', '/schedule-changes/admin', '/notifications/admin', '/system-status']
), true);
$extraModuleLinks = array_values(array_filter(
    $moduleNavigation,
    static fn (array $item): bool => !isset($knownPaths[(string) ($item['path'] ?? '')])
));

$renderNavLink = static function (array $item): void {
    $badge = $item['badge'] ?? null;
    ?>
    <a href="<?= e(url((string) $item['path'])) ?>">
        <span><?= e((string) $item['label']) ?></span>
        <?php if (is_array($badge)): ?>
            <?php
                $badgeCount = max(0, (int) ($badge['count'] ?? 0));
                $badgeColor = $badgeCount > 0 ? '#b42318' : '#2563eb';
            ?>
            <span class="nav-badge <?= e($badge['tone'] ?? 'info') ?>" style="background:<?= e($badgeColor) ?>;">
                <?= e((string) $badgeCount) ?>
            </span>
        <?php endif; ?>
    </a>
    <?php
};
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="QvarcY">
    <meta name="license" content="AGPL-3.0-or-later">
    <link rel="license" href="https://www.gnu.org/licenses/agpl-3.0.html">
    <title><?= e($title ?? $brandName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css?v=20260803-1')) ?>">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($brandName) ?>">
                <span class="brand-mark">WS</span>
                <span>
                    <strong><?= e($brandName) ?></strong>
                    <small>Universāla darba grafiku pārvaldība</small>
                </span>
            </a>

            <?php if ($currentUser): ?>
                <div class="topbar-actions">
                    <?php if ($notificationsAvailable): ?>
                        <a class="notification-bell" href="<?= e(url('/notifications')) ?>" aria-label="Paziņojumi">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18 16v-5a6 6 0 0 0-12 0v5l-2 2h16l-2-2Z"></path>
                                <path d="M9.5 20a2.5 2.5 0 0 0 5 0"></path>
                            </svg>
                            <?php if ($notificationUnreadCount > 0): ?>
                                <span class="notification-bell-badge"><?= e((string) min($notificationUnreadCount, 99)) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <button class="mobile-nav-toggle" type="button" data-nav-toggle aria-expanded="false">Izvēlne</button>
                </div>
                <nav class="nav" data-app-nav>
                    <div class="nav-group">
                        <span class="nav-group-label">Galvenais</span>
                        <?php foreach ($mainLinks as $item): ?>
                            <?php $renderNavLink($item); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($changeLinks)): ?>
                        <div class="nav-group">
                            <?php foreach ($changeLinks as $item): ?>
                                <?php $renderNavLink($item); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($currentRole === 'admin'): ?>
                        <details class="nav-menu">
                            <summary>Grafiki</summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($adminScheduleLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        <details class="nav-menu">
                            <summary>Cilvēki</summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($adminPeopleLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        <?php if (!empty($adminRequestLinks)): ?>
                            <details class="nav-menu">
                                <summary>Pieteikumi</summary>
                                <div class="nav-menu-panel">
                                    <?php foreach ($adminRequestLinks as $item): ?>
                                        <?php $renderNavLink($item); ?>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                        <details class="nav-menu">
                            <summary>Sistēma</summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($adminSystemLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if (($currentRole ?? '') !== 'admin' && !empty($requestLinks)): ?>
                        <details class="nav-menu">
                            <summary>Pieteikumi</summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($requestLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if (($currentRole ?? '') !== 'admin' && !empty($settingsLinks)): ?>
                        <details class="nav-menu">
                            <summary>Iestatījumi</summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($settingsLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if (!empty($extraModuleLinks)): ?>
                        <details class="nav-menu">
                            <summary>Papildiespējas</summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($extraModuleLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <div class="nav-user">
                        <span title="<?= e($userLabel) ?>"><?= e($userLabel) ?></span>
                        <form method="post" action="<?= e(url('/logout')) ?>">
                            <?= csrf_field() ?>
                            <button class="link-button" type="submit">Iziet</button>
                        </form>
                    </div>
                </nav>
            <?php endif; ?>
        </header>

        <main class="content">
            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php require $viewFile; ?>
        </main>
        <footer class="app-footer">
            <div class="footer-brand">
                <strong><?= e($brandName) ?></strong>
                <span>Darba grafiku plānošana, paziņojumi un piekļuves pārvaldība.</span>
                <span>© 2026 QvarcY</span>
            </div>

            <div class="footer-support">
                <span>Original project by QvarcY</span>

                <a href="<?= e(url('/about')) ?>">
                    Par sistēmu / licence
                </a>

                <a
                    href="https://github.com/QvarcY"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    GitHub
                </a>

                <a
                    href="https://github.com/QvarcY/work-schedule-manager"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Source code
                </a>

                <a
                    href="https://buymeacoffee.com/craftin"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Buy Me a Coffee
                </a>

                <a
                    href="https://www.gnu.org/licenses/agpl-3.0.html"
                    target="_blank"
                    rel="license noopener noreferrer"
                >
                    AGPL-3.0-or-later
                </a>
            </div>
        </footer>
    </div>
    <script src="<?= e(url('/assets/js/app.js?v=20260706-2')) ?>"></script>
</body>
</html>
