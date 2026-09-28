<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Brivdienu pieteikumi</h1>
    <p class="muted">Piesaki datumu, kura velies, lai tev neplano mainu. Admins pieteikumu apstiprinas vai noraidis.</p>
</section>

<section class="panel request-form-panel">
    <h2>Jauns pieteikums</h2>
    <form method="post" action="<?= e(url('/day-off-requests')) ?>">
        <?= csrf_field() ?>
        <div class="day-off-form-grid">
            <div class="form-row">
                <label>Datums</label>
                <input name="request_date" type="date" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="form-row">
                <label>Cik svarigi?</label>
                <select name="importance">
                    <option value="velams">Velams</option>
                    <option value="svarigs">Svarigs</option>
                    <option value="neatliekams">Neatliekams</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <label>Komentars</label>
            <textarea name="comment" rows="3" placeholder="Nav obligats"></textarea>
        </div>
        <button class="button" type="submit">Nosutit pieteikumu</button>
    </form>
</section>

<section class="panel">
    <h2>Mani pieteikumi</h2>
    <?php if (empty($requests)): ?>
        <p>Nav pieteikumu.</p>
    <?php else: ?>
        <div class="request-list">
            <?php foreach ($requests as $request): ?>
                <article class="request-card status-<?= e($request['status']) ?>">
                    <div>
                        <strong><?= e((string) $request['request_date']) ?></strong>
                        <span class="badge"><?= e(importance_label($request['importance'])) ?></span>
                        <span class="badge"><?= e(status_label($request['status'])) ?></span>
                    </div>
                    <?php if (!empty($request['comment'])): ?>
                        <p><?= e($request['comment']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($request['admin_comment'])): ?>
                        <p class="muted">Admina komentars: <?= e($request['admin_comment']) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
function importance_label(string $importance): string
{
    return [
        'velams' => 'Velams',
        'svarigs' => 'Svarigs',
        'neatliekams' => 'Neatliekams',
    ][$importance] ?? $importance;
}

function status_label(string $status): string
{
    return [
        'pending' => 'Gaida',
        'approved' => 'Apstiprinats',
        'rejected' => 'Noraidits',
    ][$status] ?? $status;
}
?>
