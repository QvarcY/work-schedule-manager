<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('day_off.title')) ?></h1>
    <p class="muted"><?= e(t('day_off.description')) ?></p>
</section>

<section class="panel request-form-panel">
    <h2><?= e(t('day_off.create.title')) ?></h2>
    <form method="post" action="<?= e(url('/day-off-requests')) ?>">
        <?= csrf_field() ?>
        <div class="day-off-form-grid">
            <div class="form-row">
                <label><?= e(t('day_off.fields.date')) ?></label>
                <input name="request_date" type="date" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="form-row">
                <label><?= e(t('day_off.fields.importance')) ?></label>
                <select name="importance">
                    <option value="velams"><?= e(t('day_off.importance.preferred')) ?></option>
                    <option value="svarigs"><?= e(t('day_off.importance.important')) ?></option>
                    <option value="neatliekams"><?= e(t('day_off.importance.urgent')) ?></option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <label><?= e(t('common.comment')) ?></label>
            <textarea name="comment" rows="3" placeholder="<?= e(t('common.optional')) ?>"></textarea>
        </div>
        <button class="button" type="submit"><?= e(t('day_off.create.submit')) ?></button>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('day_off.my_requests')) ?></h2>
    <?php if (empty($requests)): ?>
        <p><?= e(t('day_off.empty')) ?></p>
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
                        <p class="muted"><?= e(t('day_off.admin_comment', ['comment' => $request['admin_comment']])) ?></p>
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
        'velams' => t('day_off.importance.preferred'),
        'svarigs' => t('day_off.importance.important'),
        'neatliekams' => t('day_off.importance.urgent'),
    ][$importance] ?? $importance;
}

function status_label(string $status): string
{
    return [
        'pending' => t('day_off.status.pending'),
        'approved' => t('day_off.status.approved'),
        'rejected' => t('day_off.status.rejected'),
    ][$status] ?? $status;
}
?>
