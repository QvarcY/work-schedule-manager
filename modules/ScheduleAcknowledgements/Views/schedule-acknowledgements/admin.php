<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1>Grafiku apliecinājumi</h1>
    <p class="muted">Izvēlies publicētu grafiku un pārbaudi, kuri reģistrētie darbinieki ir apliecinājuši iepazīšanos.</p>
</section>

<section class="panel">
    <form method="get" action="<?= e(url('/schedule-acknowledgements/admin')) ?>" class="compact-form">
        <select name="schedule_id" required>
            <option value="">Izvēlies grafiku</option>
            <?php foreach ($schedules as $schedule): ?>
                <option value="<?= e((string) $schedule['id']) ?>" <?= (int) $selectedScheduleId === (int) $schedule['id'] ? 'selected' : '' ?>>
                    <?= e($schedule['schedule_name']) ?> / <?= e($schedule['month']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">Skatīt</button>
    </form>
</section>

<?php if ($selectedScheduleId > 0): ?>
    <section class="panel">
        <h2>Statuss</h2>
        <?php if (empty($rows)): ?>
            <p>Šim grafikam nav piesaistītu reģistrētu darbinieku.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Darbinieks</th>
                            <th>Grafikā</th>
                            <th>Statuss</th>
                            <th>Apliecināts</th>
                            <th>Komentārs</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                                $fullName = $fullName !== '' ? $fullName : (string) $row['username'];
                                $acknowledged = (int) ($row['acknowledgement_current'] ?? 0) === 1;
                            ?>
                            <tr class="<?= $acknowledged ? 'journal-admin-row' : 'journal-user-row' ?>">
                                <td><strong><?= e($fullName) ?></strong></td>
                                <td><?= e($row['schedule_name'] ?? '') ?></td>
                                <td>
                                    <span class="journal-action <?= $acknowledged ? 'admin' : 'user' ?>">
                                        <?= $acknowledged ? 'Iepazinos' : 'Gaida' ?>
                                    </span>
                                </td>
                                <td><?= e($row['acknowledged_at'] ?: '-') ?></td>
                                <td><?= e($row['comment'] ?: '') ?></td>
                                <td><?= e($row['ip_address'] ?: '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
