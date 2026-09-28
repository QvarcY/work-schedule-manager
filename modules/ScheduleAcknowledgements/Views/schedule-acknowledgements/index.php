<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Grafika apliecinājumi</h1>
    <p class="muted">Šeit redzi publicētos grafikus, kuros esi pievienots kā reģistrēts darbinieks.</p>
</section>

<section class="panel">
    <?php if (empty($items)): ?>
        <p>Šobrīd nav grafiku, kuriem nepieciešams apliecinājums.</p>
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
                            <?= $acknowledged ? 'Iepazinos' : 'Gaida apliecinājumu' ?>
                        </span>
                    </div>

                    <p class="muted">
                        Maiņas: <?= e((string) $item['shifts_count']) ?>,
                        stundas: <?= e((string) $item['hours']) ?> h.
                    </p>

                    <?php if ($acknowledged): ?>
                        <p>Apliecināts: <strong><?= e((string) $item['acknowledged_at']) ?></strong></p>
                        <?php if (!empty($item['comment'])): ?>
                            <p class="muted">Komentārs: <?= e($item['comment']) ?></p>
                        <?php endif; ?>
                        <a class="button secondary" href="<?= e(url('/schedules/show?id=' . $item['id'])) ?>">Skatīt grafiku</a>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('/schedule-acknowledgements/ack')) ?>" class="ack-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="schedule_id" value="<?= e((string) $item['id']) ?>">
                            <div class="form-row">
                                <label>Komentārs</label>
                                <input name="comment" type="text" placeholder="Nav obligāts">
                            </div>
                            <div class="toolbar">
                                <a class="button secondary" href="<?= e(url('/schedules/show?id=' . $item['id'])) ?>">Skatīt grafiku</a>
                                <button class="button" type="submit">Apliecinu, ka iepazinos</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
