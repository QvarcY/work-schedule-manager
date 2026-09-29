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
$currentLocale = current_locale();
$availableLocales = available_locales();
$localeReturnTo = (string) ($_SERVER['REQUEST_URI'] ?? '/');

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
    ['label' => t('nav.overview'), 'path' => '/'],
];

if (isset($moduleByPath['/employee/profile'])) {
    $mainLinks[] = ['label' => t('nav.profile'), 'path' => '/employee/profile'];
}

$mainLinks[] = ['label' => t('nav.schedules'), 'path' => '/schedules'];

$requestLinks = [];
foreach (['/day-off-requests' => t('nav.day_off'), '/schedule-acknowledgements' => t('nav.acknowledgements')] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $requestLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$settingsLinks = [];
if ($notificationsAvailable) {
    $settingsLinks[] = ['label' => t('nav.notification_settings'), 'path' => '/notifications'];
}

$changeLinks = [];
if (($currentRole ?? '') === 'admin' && isset($moduleByPath['/schedule-changes/admin'])) {
    $changeLinks[] = ['label' => t('nav.changes'), 'path' => '/schedule-changes/admin', 'badge' => $moduleByPath['/schedule-changes/admin']['badge'] ?? null];
} elseif (isset($moduleByPath['/schedule-changes'])) {
    $changeLinks[] = ['label' => t('nav.changes'), 'path' => '/schedule-changes', 'badge' => $moduleByPath['/schedule-changes']['badge'] ?? null];
}

$adminScheduleLinks = [
    ['label' => t('nav.new_schedule'), 'path' => '/schedules/create'],
    ['label' => t('nav.shift_types'), 'path' => '/shift-types'],
];

$adminPeopleLinks = [
    ['label' => t('nav.users'), 'path' => '/users'],
];

foreach (['/employee-profiles/admin' => t('nav.employees'), '/user-invitations/admin' => t('nav.invitations')] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $adminPeopleLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$adminRequestLinks = [];
foreach (['/day-off-requests/admin' => t('nav.day_off_requests'), '/schedule-acknowledgements/admin' => t('nav.schedule_acknowledgements')] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $adminRequestLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$adminSystemLinks = [
    ['label' => t('nav.modules'), 'path' => '/modules'],
    ['label' => t('nav.activity_log'), 'path' => '/journal'],
];

foreach (['/notifications/admin' => t('nav.notifications_admin'), '/system-status' => t('nav.system_status'), '/translations' => t('nav.translations')] as $path => $label) {
    if (isset($moduleByPath[$path])) {
        $adminSystemLinks[] = ['label' => $label, 'path' => $path, 'badge' => $moduleByPath[$path]['badge'] ?? null];
    }
}

$knownPaths = array_fill_keys(array_merge(
    ['/employee/profile', '/day-off-requests', '/schedule-acknowledgements', '/schedule-changes'],
    ['/employee-profiles/admin', '/user-invitations/admin', '/day-off-requests/admin', '/schedule-acknowledgements/admin', '/schedule-changes/admin', '/notifications/admin', '/system-status', '/translations']
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

$renderLocaleSwitcher = static function () use ($availableLocales, $currentLocale, $localeReturnTo): void {
    if (count($availableLocales) < 2) {
        return;
    }
    ?>
    <form class="locale-switcher" method="post" action="<?= e(url('/locale')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="<?= e($localeReturnTo) ?>">
        <label class="sr-only" for="app-locale"><?= e(t('language.select')) ?></label>
        <select id="app-locale" name="locale" aria-label="<?= e(t('language.select')) ?>">
            <?php foreach ($availableLocales as $localeCode => $localeName): ?>
                <option value="<?= e($localeCode) ?>" <?= $localeCode === $currentLocale ? 'selected' : '' ?>>
                    <?= e($localeName) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button secondary" type="submit"><?= e(t('language.change')) ?></button>
    </form>
    <?php
};
?>
<!DOCTYPE html>
<html lang="<?= e($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="QvarcY">
    <meta name="license" content="AGPL-3.0-or-later">
    <link rel="license" href="https://www.gnu.org/licenses/agpl-3.0.html">
    <title><?= e($title ?? $brandName) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css?v=20260929-1')) ?>">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($brandName) ?>">
                <span class="brand-mark">WS</span>
                <span>
                    <strong><?= e($brandName) ?></strong>
                    <small><?= e(t('brand.subtitle')) ?></small>
                </span>
            </a>

            <?php if ($currentUser): ?>
                <div class="topbar-actions">
                    <?php if ($notificationsAvailable): ?>
                        <a class="notification-bell" href="<?= e(url('/notifications')) ?>" aria-label="<?= e(t('nav.notifications')) ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18 16v-5a6 6 0 0 0-12 0v5l-2 2h16l-2-2Z"></path>
                                <path d="M9.5 20a2.5 2.5 0 0 0 5 0"></path>
                            </svg>
                            <?php if ($notificationUnreadCount > 0): ?>
                                <span class="notification-bell-badge"><?= e((string) min($notificationUnreadCount, 99)) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <button class="mobile-nav-toggle" type="button" data-nav-toggle aria-expanded="false"><?= e(t('nav.menu')) ?></button>
                </div>
                <nav class="nav" data-app-nav>
                    <div class="nav-group">
                        <span class="nav-group-label"><?= e(t('nav.main')) ?></span>
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
                            <summary><?= e(t('nav.schedules')) ?></summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($adminScheduleLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        <details class="nav-menu">
                            <summary><?= e(t('nav.people')) ?></summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($adminPeopleLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        <?php if (!empty($adminRequestLinks)): ?>
                            <details class="nav-menu">
                                <summary><?= e(t('nav.requests')) ?></summary>
                                <div class="nav-menu-panel">
                                    <?php foreach ($adminRequestLinks as $item): ?>
                                        <?php $renderNavLink($item); ?>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                        <details class="nav-menu">
                            <summary><?= e(t('nav.system')) ?></summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($adminSystemLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if (($currentRole ?? '') !== 'admin' && !empty($requestLinks)): ?>
                        <details class="nav-menu">
                            <summary><?= e(t('nav.requests')) ?></summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($requestLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if (($currentRole ?? '') !== 'admin' && !empty($settingsLinks)): ?>
                        <details class="nav-menu">
                            <summary><?= e(t('nav.settings')) ?></summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($settingsLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if (!empty($extraModuleLinks)): ?>
                        <details class="nav-menu">
                            <summary><?= e(t('nav.extras')) ?></summary>
                            <div class="nav-menu-panel">
                                <?php foreach ($extraModuleLinks as $item): ?>
                                    <?php $renderNavLink($item); ?>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php $renderLocaleSwitcher(); ?>

                    <div class="nav-user">
                        <span title="<?= e($userLabel) ?>"><?= e($userLabel) ?></span>
                        <form method="post" action="<?= e(url('/logout')) ?>">
                            <?= csrf_field() ?>
                            <button class="link-button" type="submit"><?= e(t('nav.logout')) ?></button>
                        </form>
                    </div>
                </nav>
            <?php else: ?>
                <div class="topbar-actions">
                    <?php $renderLocaleSwitcher(); ?>
                </div>
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
                <span><?= e(t('footer.description')) ?></span>
                <span>© 2026 QvarcY</span>
            </div>

            <div class="footer-support">
                <span><?= e(t('footer.original_project')) ?></span>

                <a href="<?= e(url('/about')) ?>">
                    <?= e(t('footer.about')) ?>
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
                    <?= e(t('footer.source_code')) ?>
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
    <script>
        window.appTranslations = <?= json_encode([
            'js.holiday_default' => t('schedules.holiday_default'),
            'js.regular_day_off' => t('schedules.regular_day_off'),
            'js.shift_count' => t('schedules.shift_count'),
            'js.no_employees_filled' => t('schedules.editor.no_employees_filled'),
            'js.edit_short' => t('common.edit_short'),
            'js.employee_already_linked' => t('schedules.editor.employee_already_linked'),
            'js.not_linked' => t('schedules.editor.not_linked'),
            'js.link_employee' => t('schedules.editor.link_employee'),
            'js.employee_name' => t('schedules.employee'),
            'js.delete' => t('common.delete'),
            'js.select_registered_employee' => t('schedules.editor.select_registered_employee'),
            'js.employee_already_added' => t('schedules.editor.employee_already_added'),
            'js.preparing_image' => t('schedules.export.preparing'),
            'js.image_failed' => t('schedules.export.failed'),
            'js.schedule_default' => t('schedules.default_name'),
            'js.holidays' => t('schedules.holidays'),
            'js.hours_summary' => t('schedules.hours_summary'),
            'js.legend' => t('schedules.legend'),
            'js.no_employees' => t('schedules.no_employees'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= e(url('/assets/js/app.js?v=20260929-1')) ?>"></script>
</body>
</html>
