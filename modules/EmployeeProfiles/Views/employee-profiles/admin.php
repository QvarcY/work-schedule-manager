<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Darbinieki</h1>
    <p class="muted">Reģistrētie darbinieki, kuri var pieslēgties savam profilam. Šeit var noteikt arī grafika redzamību katram darbiniekam.</p>
</section>

<section class="panel">
    <?php if (empty($employees)): ?>
        <p>Nav reģistrētu darbinieku.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vārds</th>
                        <th>Uzvārds</th>
                        <th>E-pasts</th>
                        <th>Telefons</th>
                        <th>Grafikā redz</th>
                        <th>Registrēts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?= e($employee['first_name'] ?? '') ?></td>
                            <td><?= e($employee['last_name'] ?? '') ?></td>
                            <td><?= e($employee['email'] ?? $employee['username']) ?></td>
                            <td><?= e($employee['phone'] ?? '') ?></td>
                            <td>
                                <form method="post" action="<?= e(url('/employee-profiles/admin/visibility')) ?>" class="inline-select-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= e((string) $employee['id']) ?>">
                                    <select name="schedule_view_mode">
                                        <?php foreach ([
                                            'full' => 'Pilnu grafiku',
                                            'own' => 'Tikai savas maiņas',
                                            'day' => 'Tikai dienas maiņas',
                                            'night' => 'Tikai nakts maiņas',
                                        ] as $mode => $label): ?>
                                            <option value="<?= e($mode) ?>" <?= ($employee['schedule_view_mode'] ?? 'full') === $mode ? 'selected' : '' ?>>
                                                <?= e($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button secondary" type="submit">Saglabāt</button>
                                </form>
                            </td>
                            <td><?= e((string) $employee['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
