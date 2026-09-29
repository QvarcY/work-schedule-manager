<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <div class="section-heading">
        <div>
            <h1><?= e(t('shift_types.title')) ?></h1>
            <p class="muted"><?= e(t('shift_types.description')) ?></p>
        </div>
    </div>
</section>

<section class="panel">
    <h2><?= e(t('shift_types.create.title')) ?></h2>
    <form method="post" action="<?= e(url('/shift-types/store')) ?>" class="compact-form shift-type-form">
        <?= csrf_field() ?>
        <input name="code" type="text" maxlength="10" placeholder="<?= e(t('shift_types.fields.code')) ?>" required>
        <input name="label" type="text" placeholder="<?= e(t('shift_types.fields.name')) ?>" required>
        <input name="hours" type="number" step="0.25" value="0" title="<?= e(t('shift_types.fields.hours')) ?>" required>
        <label class="color-swatch-field"><span><?= e(t('shift_types.fields.background')) ?></span><input name="background_color" type="color" value="#ffffff"></label>
        <label class="color-swatch-field"><span><?= e(t('shift_types.fields.text')) ?></span><input name="text_color" type="color" value="#000000"></label>
        <input name="sort_order" type="number" value="0" title="<?= e(t('shift_types.fields.order')) ?>">
        <label class="check-option"><input name="counts_as_shift" type="checkbox" checked> <?= e(t('shift_types.fields.counts_as_shift')) ?></label>
        <label class="check-option"><input name="is_leader_type" type="checkbox"> <?= e(t('shift_types.fields.leader')) ?></label>
        <label class="check-option"><input name="active" type="checkbox" checked> <?= e(t('shift_types.fields.active')) ?></label>
        <button class="button" type="submit"><?= e(t('common.add')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('shift_types.existing')) ?></h2>
    <div class="settings-list">
        <?php foreach ($shiftTypes as $type): ?>
            <article class="setting-row">
                <form method="post" action="<?= e(url('/shift-types/update')) ?>" class="compact-form shift-type-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e((string) $type['id']) ?>">
                    <span class="shift-token" style="background: <?= e($type['background_color']) ?>; color: <?= e($type['text_color']) ?>;">
                        <?= e($type['code']) ?>
                    </span>
                    <input name="code" type="text" maxlength="10" value="<?= e($type['code']) ?>" required>
                    <input name="label" type="text" value="<?= e($type['label']) ?>" required>
                    <input name="hours" type="number" step="0.25" value="<?= e((string) $type['hours']) ?>" required title="<?= e(t('shift_types.fields.hours')) ?>">
                    <label class="color-swatch-field"><span><?= e(t('shift_types.fields.background')) ?></span><input name="background_color" type="color" value="<?= e($type['background_color']) ?>"></label>
                    <label class="color-swatch-field"><span><?= e(t('shift_types.fields.text')) ?></span><input name="text_color" type="color" value="<?= e($type['text_color']) ?>"></label>
                    <input name="sort_order" type="number" value="<?= e((string) $type['sort_order']) ?>" title="<?= e(t('shift_types.fields.order')) ?>">
                    <label class="check-option"><input name="counts_as_shift" type="checkbox" <?= (int) $type['counts_as_shift'] === 1 ? 'checked' : '' ?>> <?= e(t('shift_types.fields.counts_as_shift')) ?></label>
                    <label class="check-option"><input name="is_leader_type" type="checkbox" <?= (int) $type['is_leader_type'] === 1 ? 'checked' : '' ?>> <?= e(t('shift_types.fields.leader')) ?></label>
                    <label class="check-option"><input name="active" type="checkbox" <?= (int) $type['active'] === 1 ? 'checked' : '' ?>> <?= e(t('shift_types.fields.active')) ?></label>
                    <button class="button secondary" type="submit"><?= e(t('common.save')) ?></button>
                </form>
                <form method="post" action="<?= e(url('/shift-types/delete')) ?>" data-confirm="<?= e(t('shift_types.delete.confirm')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e((string) $type['id']) ?>">
                    <button class="link-button" type="submit"><?= e(t('common.delete')) ?></button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
