<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <div class="page-title-row">
        <div>
            <h1><?= e(t('modules.title')) ?></h1>
            <p class="muted"><?= e(t('modules.description')) ?></p>
        </div>
    </div>
</section>

<?php if (!empty($moduleError)): ?>
    <section class="panel">
        <div class="alert error"><?= e($moduleError) ?></div>
        <p><?= e(t('modules.load_error_hint')) ?></p>
    </section>
<?php endif; ?>

<section class="panel">
    <h2><?= e(t('modules.upload.title')) ?></h2>
    <form method="post" action="<?= e(url('/modules/upload')) ?>" enctype="multipart/form-data" class="compact-form">
        <?= csrf_field() ?>
        <input type="file" name="module_zip" accept=".zip" required>
        <button class="button" type="submit"><?= e(t('modules.upload.submit')) ?></button>
    </form>
    <p class="muted"><?= e(t('modules.upload.hint')) ?></p>
</section>

<section class="panel">
    <h2><?= e(t('modules.available')) ?></h2>
    <?php if (empty($availableModules)): ?>
        <p><?= e(t('modules.empty')) ?></p>
    <?php else: ?>
        <div class="module-grid">
            <?php foreach ($availableModules as $module): ?>
                <?php
                    $installed = $installedModules[$module['name']] ?? null;
                    $isActive = $installed ? (int) ($installed['active'] ?? 1) === 1 : false;
                ?>
                <article class="module-card">
                    <div>
                        <h3><?= e($module['title'] ?? $module['name']) ?></h3>
                        <p class="muted"><?= e($module['description'] ?? '') ?></p>
                    </div>
                    <dl class="module-meta">
                        <div><dt><?= e(t('modules.code')) ?></dt><dd><?= e($module['name']) ?></dd></div>
                        <div><dt><?= e(t('modules.version')) ?></dt><dd><?= e($module['version'] ?? '1.0.0') ?></dd></div>
                        <div><dt><?= e(t('modules.status')) ?></dt><dd><?= e($installed ? ($isActive ? t('modules.status.active') : t('modules.status.inactive')) : t('modules.status.not_installed')) ?></dd></div>
                    </dl>

                    <div class="toolbar module-actions">
                        <?php if (!$installed): ?>
                            <form method="post" action="<?= e(url('/modules/install')) ?>" data-confirm="<?= e(t('modules.install.confirm')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                <button class="button" type="submit"><?= e(t('modules.install.submit')) ?></button>
                            </form>
                        <?php else: ?>
                            <?php if ($isActive): ?>
                                <form method="post" action="<?= e(url('/modules/deactivate')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                    <button class="button secondary" type="submit"><?= e(t('modules.deactivate.submit')) ?></button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/modules/activate')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                    <button class="button secondary" type="submit"><?= e(t('modules.activate.submit')) ?></button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="<?= e(url('/modules/uninstall')) ?>" data-confirm="<?= e(t('modules.uninstall.confirm')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                <label class="check-option"><input type="checkbox" name="delete_files" value="1"> <?= e(t('modules.uninstall.delete_files')) ?></label>
                                <button class="button danger" type="submit"><?= e(t('modules.uninstall.submit')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2><?= e(t('modules.structure.title')) ?></h2>
    <p><?= e(t('modules.structure.description')) ?></p>
</section>
