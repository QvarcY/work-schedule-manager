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
            <h1>Apzimejumi</h1>
            <p class="muted">Maiņu kodi, krasas un aprekinamas stundas.</p>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Jauns apzimejums</h2>
    <form method="post" action="<?= e(url('/shift-types/store')) ?>" class="compact-form shift-type-form">
        <?= csrf_field() ?>
        <input name="code" type="text" maxlength="10" placeholder="Kods" required>
        <input name="label" type="text" placeholder="Nosaukums" required>
        <input name="hours" type="number" step="0.25" value="0" title="Stundas" required>
        <label class="color-swatch-field"><span>Fons</span><input name="background_color" type="color" value="#ffffff"></label>
        <label class="color-swatch-field"><span>Teksts</span><input name="text_color" type="color" value="#000000"></label>
        <input name="sort_order" type="number" value="0" title="Seciba">
        <label class="check-option"><input name="counts_as_shift" type="checkbox" checked> Skaitit ka mainu</label>
        <label class="check-option"><input name="is_leader_type" type="checkbox"> Vaditaja apzimejums</label>
        <label class="check-option"><input name="active" type="checkbox" checked> Aktīvs saraksta</label>
        <button class="button" type="submit">Pievienot</button>
    </form>
</section>

<section class="panel">
    <h2>Esošie apzimejumi</h2>
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
                    <input name="hours" type="number" step="0.25" value="<?= e((string) $type['hours']) ?>" required title="Stundas">
                    <label class="color-swatch-field"><span>Fons</span><input name="background_color" type="color" value="<?= e($type['background_color']) ?>"></label>
                    <label class="color-swatch-field"><span>Teksts</span><input name="text_color" type="color" value="<?= e($type['text_color']) ?>"></label>
                    <input name="sort_order" type="number" value="<?= e((string) $type['sort_order']) ?>" title="Seciba">
                    <label class="check-option"><input name="counts_as_shift" type="checkbox" <?= (int) $type['counts_as_shift'] === 1 ? 'checked' : '' ?>> Skaitit ka mainu</label>
                    <label class="check-option"><input name="is_leader_type" type="checkbox" <?= (int) $type['is_leader_type'] === 1 ? 'checked' : '' ?>> Vaditaja apzimejums</label>
                    <label class="check-option"><input name="active" type="checkbox" <?= (int) $type['active'] === 1 ? 'checked' : '' ?>> Aktīvs</label>
                    <button class="button secondary" type="submit">Saglabat</button>
                </form>
                <form method="post" action="<?= e(url('/shift-types/delete')) ?>" data-confirm="Dzest so apzimejumu? Ja tas jau izmantots grafikos, dzesana netiks veikta.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e((string) $type['id']) ?>">
                    <button class="link-button" type="submit">Dzest</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
