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
    <p class="muted">Apskati darbinieku pieteiktas brivas dienas un pienem lemumu.</p>
</section>

<section class="panel">
    <?php if (empty($requests)): ?>
        <p>Nav pieteikumu.</p>
    <?php else: ?>
        <div class="request-list">
            <?php foreach ($requests as $request): ?>
                <?php
                    $employeeName = trim((string) ($request['first_name'] ?? '') . ' ' . (string) ($request['last_name'] ?? ''));
                    $employeeName = $employeeName !== '' ? $employeeName : (string) ($request['username'] ?? '');
                    $sameDateCount = (int) ($dateCounts[$request['request_date']] ?? 1);
                ?>
                <article class="request-card status-<?= e($request['status']) ?>">
                    <div class="request-card-head">
                        <div>
                            <strong><?= e($employeeName) ?></strong>
                            <span><?= e((string) $request['request_date']) ?></span>
                        </div>
                        <div class="request-badges">
                            <span class="badge"><?= e(importance_label($request['importance'])) ?></span>
                            <span class="badge"><?= e(status_label($request['status'])) ?></span>
                            <?php if ($sameDateCount > 1): ?>
                                <span class="badge warning"><?= $sameDateCount ?> pieteikumi saja diena</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($request['comment'])): ?>
                        <p><?= e($request['comment']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($request['admin_comment'])): ?>
                        <p class="muted">Admina komentars: <?= e($request['admin_comment']) ?></p>
                    <?php endif; ?>

                    <?php if ($request['status'] === 'pending'): ?>
                        <form class="request-decision-form" method="post" action="<?= e(url('/day-off-requests/admin/decide')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) $request['id']) ?>">
                            <input name="admin_comment" type="text" placeholder="Komentars, ja nepieciesams">
                            <button class="button secondary" name="status" value="approved" type="submit">Apstiprinat</button>
                            <button class="button danger" name="status" value="rejected" type="submit">Noraidit</button>
                        </form>
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
