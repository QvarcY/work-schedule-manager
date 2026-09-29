<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('translations.title')) ?></h1>
    <p class="muted"><?= e(t('translations.description')) ?></p>
</section>

<section class="panel">
    <div class="page-title-row">
        <div>
            <h2><?= e(t('translations.coverage.title')) ?></h2>
            <p class="muted"><?= e(t('translations.coverage.fallback', ['locale' => $fallbackLocale])) ?></p>
        </div>
        <a class="button secondary" href="<?= e(url('/translations/export?locale=' . $fallbackLocale)) ?>">
            <?= e(t('translations.export_template')) ?>
        </a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th><?= e(t('translations.table.language')) ?></th>
                    <th><?= e(t('translations.table.locale')) ?></th>
                    <th><?= e(t('translations.table.coverage')) ?></th>
                    <th><?= e(t('translations.table.missing')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coverage as $item): ?>
                    <?php $percentage = $item['total'] > 0 ? (int) round(($item['translated'] / $item['total']) * 100) : 100; ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><code><?= e($item['locale']) ?></code></td>
                        <td><?= e((string) $item['translated']) ?> / <?= e((string) $item['total']) ?> (<?= e((string) $percentage) ?>%)</td>
                        <td>
                            <?php if (empty($item['missing'])): ?>
                                <?= e(t('translations.complete')) ?>
                            <?php else: ?>
                                <details>
                                    <summary><?= e(t('translations.missing_count', ['count' => count($item['missing'])])) ?></summary>
                                    <ul class="translation-key-list">
                                        <?php foreach ($item['missing'] as $key): ?>
                                            <li><code><?= e($key) ?></code></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="toolbar">
                                <a class="button secondary" href="<?= e(url('/translations/export?locale=' . $item['locale'])) ?>">
                                    <?= e(t('translations.export')) ?>
                                </a>
                                <?php if ($item['has_override']): ?>
                                    <form method="post" action="<?= e(url('/translations/reset')) ?>" data-confirm="<?= e(t('translations.reset.confirm')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="locale" value="<?= e($item['locale']) ?>">
                                        <button class="button danger" type="submit"><?= e(t('translations.reset')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2><?= e(t('translations.import.title')) ?></h2>
    <p><?= e(t('translations.import.description')) ?></p>

    <form method="post" action="<?= e(url('/translations/import')) ?>" enctype="multipart/form-data" class="compact-form">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="translation-locale"><?= e(t('translations.table.locale')) ?></label>
            <input id="translation-locale" name="locale" type="text" maxlength="6" pattern="[A-Za-z]{2,3}([_-][A-Za-z]{2})?" placeholder="en" required>
        </div>
        <div class="form-row">
            <label for="translation-catalogue"><?= e(t('translations.import.file')) ?></label>
            <input id="translation-catalogue" name="catalogue" type="file" accept="application/json,.json" required>
        </div>
        <button class="button" type="submit"><?= e(t('translations.import.submit')) ?></button>
    </form>

    <p class="muted"><?= e(t('translations.import.note')) ?></p>
</section>
