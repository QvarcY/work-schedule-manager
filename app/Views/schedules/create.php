<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Jauns grafiks</h1>
    <form method="post" action="<?= e(url('/schedules/store')) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="schedule_name">Grafika nosaukums</label>
            <input id="schedule_name" name="schedule_name" type="text" required>
        </div>
        <div class="form-row">
            <label for="month">Mēnesis</label>
            <input id="month" name="month" type="text" placeholder="Piemēram: MAIJS 2026" required>
        </div>
        <button class="button" type="submit">Izveidot</button>
    </form>
</section>

<section class="panel">
    <h2>Pieejamie apzīmējumi</h2>
    <div class="grid">
        <?php foreach ($shiftTypes as $type): ?>
            <div>
                <span class="shift-token" style="background: <?= e($type['background_color']) ?>; color: <?= e($type['text_color']) ?>;">
                    <?= e($type['code']) ?>
                </span>
                <?= e($type['label']) ?>, <?= e((string) $type['hours']) ?> h
            </div>
        <?php endforeach; ?>
    </div>
</section>
