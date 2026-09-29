<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e(t('acknowledgements.title')) ?></h1>
    <p class="muted"><?= e(t('acknowledgements.description')) ?></p>
</section>

<section class="panel">
    <?php if (empty($items)): ?>
        <p><?= e(t('acknowledgements.empty')) ?></p>
    <?php else: ?>
        <div class="request-list">
            <?php foreach ($items as $item): ?>
                <?php $acknowledged = (int) ($item['acknowledgement_current'] ?? 0) === 1; ?>
                <article class="request-card <?= $acknowledged ? 'status-approved' : 'status-pending' ?>">
                    <div class="request-card-head">
                        <div>
                            <strong><?= e($item['schedule_name']) ?></strong>
                            <span><?= e($item['month']) ?></span>
                        </div>
                        <span class="badge <?= $acknowledged ? '' : 'warning' ?>">
                            <?= e($acknowledged ? t('acknowledgements.status.acknowledged') : t('acknowledgements.status.pending')) ?>
                        </span>
                    </div>

                    <p class="muted">
                        <?= e(t('acknowledgements.summary', ['shifts' => $item['shifts_count'], 'hours' => $item['hours']])) ?>
                    </p>

                    <?php if ($acknowledged): ?>
                        <p><?= e(t('acknowledgements.acknowledged_at', ['date' => $item['acknowledged_at']])) ?></p>
                        <?php if (!empty($item['comment'])): ?>
                            <p class="muted"><?= e(t('acknowledgements.comment', ['comment' => $item['comment']])) ?></p>
                        <?php endif; ?>
                        <a class="button secondary" href="<?= e(url('/schedules/show?id=' . $item['id'])) ?>"><?= e(t('acknowledgements.view_schedule')) ?></a>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('/schedule-acknowledgements/ack')) ?>" class="ack-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="schedule_id" value="<?= e((string) $item['id']) ?>">
                            <div class="form-row">
                                <label><?= e(t('common.comment')) ?></label>
                                <input name="comment" type="text" placeholder="<?= e(t('common.optional')) ?>">
                            </div>
                            <div class="toolbar">
                                <a class="button secondary" href="<?= e(url('/schedules/show?id=' . $item['id'])) ?>"><?= e(t('acknowledgements.view_schedule')) ?></a>
                                <button class="button" type="submit"><?= e(t('acknowledgements.submit')) ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
