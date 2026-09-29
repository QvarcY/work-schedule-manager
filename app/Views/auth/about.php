<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$appName = trim((string) env_value('APP_NAME', 'Work Schedule Manager'));

if ($appName === '') {
    $appName = 'Work Schedule Manager';
}

?>

<section class="panel" style="max-width: 980px; margin: 32px auto;">
    <div class="page-title-row">
        <div>
            <h1><?= e($appName) ?></h1>
            <p class="muted"><?= e(t('about.tagline')) ?></p>
        </div>

        <div class="toolbar no-print">
            <a class="button secondary" href="<?= e(url('/login')) ?>">
                <?= e(t('common.login')) ?>
            </a>
        </div>
    </div>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2><?= e(t('about.system.title')) ?></h2>

    <p><?= e(t('about.system.p1', ['app' => $appName])) ?></p>

    <p><?= e(t('about.system.p2')) ?></p>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2><?= e(t('about.features.title')) ?></h2>

    <div class="feature-grid">
        <article class="feature-card">
            <h3><?= e(t('about.features.schedules.title')) ?></h3>
            <p><?= e(t('about.features.schedules.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.shifts.title')) ?></h3>
            <p><?= e(t('about.features.shifts.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.employees.title')) ?></h3>
            <p><?= e(t('about.features.employees.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.roles.title')) ?></h3>
            <p><?= e(t('about.features.roles.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.days_off.title')) ?></h3>
            <p><?= e(t('about.features.days_off.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.acknowledgements.title')) ?></h3>
            <p><?= e(t('about.features.acknowledgements.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.notifications.title')) ?></h3>
            <p><?= e(t('about.features.notifications.text')) ?></p>
        </article>

        <article class="feature-card">
            <h3><?= e(t('about.features.history.title')) ?></h3>
            <p><?= e(t('about.features.history.text')) ?></p>
        </article>
    </div>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2><?= e(t('about.modular.title')) ?></h2>

    <p><?= e(t('about.modular.p1')) ?></p>

    <p><?= e(t('about.modular.p2')) ?></p>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2><?= e(t('about.access.title')) ?></h2>

    <p><?= e(t('about.access.text')) ?></p>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 32px;">
    <h2><?= e(t('about.hosting.title')) ?></h2>

    <p><?= e(t('about.hosting.text', ['app' => $appName])) ?></p>
</section>

<section
    id="project-attribution"
    class="panel"
    style="max-width: 980px; margin: 0 auto 32px;"
>
    <h2><?= e(t('about.legal.title')) ?></h2>

    <p>
        <strong><?= e(t('about.legal.original')) ?></strong>
        <?= e(t('about.legal.origin')) ?>
    </p>

    <p>
        <a
            href="https://github.com/QvarcY"
            target="_blank"
            rel="noopener noreferrer"
        ><?= e(t('about.legal.github')) ?></a>
        ·
        <a
            href="https://github.com/QvarcY/work-schedule-manager"
            target="_blank"
            rel="noopener noreferrer"
        ><?= e(t('about.legal.source')) ?></a>
        ·
        <a
            href="https://buymeacoffee.com/craftin"
            target="_blank"
            rel="noopener noreferrer"
        ><?= e(t('about.legal.support')) ?></a>
    </p>

    <p><?= e(t('about.legal.license')) ?></p>

    <p class="muted"><?= e(t('about.legal.modified')) ?></p>

    <p class="muted"><?= e(t('about.legal.warranty')) ?></p>
</section>
