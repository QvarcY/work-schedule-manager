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
            <h1>Moduli</h1>
            <p class="muted">Augshupielade, instaleshana, aktivizeshana un atinstaleshana.</p>
        </div>
    </div>
</section>

<?php if (!empty($moduleError)): ?>
    <section class="panel">
        <div class="alert error"><?= e($moduleError) ?></div>
        <p>Parbaudi, vai serveri ir augshupieladeta visa <code>app/Services</code> un <code>modules</code> mape.</p>
    </section>
<?php endif; ?>

<section class="panel">
    <h2>Augshupieladet moduli</h2>
    <form method="post" action="<?= e(url('/modules/upload')) ?>" enctype="multipart/form-data" class="compact-form">
        <?= csrf_field() ?>
        <input type="file" name="module_zip" accept=".zip" required>
        <button class="button" type="submit">Augshupieladet ZIP</button>
    </form>
    <p class="muted">ZIP faila sakne vai viena apaksmape satur <code>module.json</code>.</p>
</section>

<section class="panel">
    <h2>Pieejamie moduli</h2>
    <?php if (empty($availableModules)): ?>
        <p>Nav atrastu modulu.</p>
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
                        <div><dt>Kods</dt><dd><?= e($module['name']) ?></dd></div>
                        <div><dt>Versija</dt><dd><?= e($module['version'] ?? '1.0.0') ?></dd></div>
                        <div><dt>Statuss</dt><dd><?= $installed ? ($isActive ? 'Aktivs' : 'Neaktivs') : 'Nav instalets' ?></dd></div>
                    </dl>

                    <div class="toolbar module-actions">
                        <?php if (!$installed): ?>
                            <form method="post" action="<?= e(url('/modules/install')) ?>" data-confirm="Instalet so moduli?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                <button class="button" type="submit">Instalet</button>
                            </form>
                        <?php else: ?>
                            <?php if ($isActive): ?>
                                <form method="post" action="<?= e(url('/modules/deactivate')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                    <button class="button secondary" type="submit">Deaktivizet</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/modules/activate')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                    <button class="button secondary" type="submit">Aktivizet</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="<?= e(url('/modules/uninstall')) ?>" data-confirm="Atinstalet moduli? Datu tabulas tiks dzestas tikai tad, ja modulim ir uninstall migracijas.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="module" value="<?= e($module['name']) ?>">
                                <label class="check-option"><input type="checkbox" name="delete_files" value="1"> Dzest failus</label>
                                <button class="button danger" type="submit">Atinstalet</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Modula struktura</h2>
    <p>Modulis dzivo <code>modules/ModulaNosaukums/</code> mape ar <code>module.json</code>, migracijam un, ja vajag, <code>routes.php</code>.</p>
</section>
