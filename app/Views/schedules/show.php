<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$daySettings = [];
$holidays = [];
foreach ($schedule['day_settings'] as $setting) {
    $daySettings[(int) $setting['day_number']] = $setting;
    if ($setting['day_type'] === 'holiday') {
        $holidays[(int) $setting['day_number']] = $setting;
    }
}
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$canViewHoursSummary = $isAdmin || (int) ($currentUser['show_hours_summary'] ?? 0) === 1;
$weekRanges = [
    [1, 7],
    [8, 14],
    [15, 21],
    [22, 31],
];
?>

<section class="panel">
    <div class="page-title-row">
        <div>
            <h1><?= e($schedule['schedule_name']) ?></h1>
            <p class="muted"><?= e($schedule['month']) ?> · <?= e($schedule['status']) ?></p>
        </div>
        <div class="toolbar no-print">
            <button class="button secondary" type="button" onclick="window.print()">Drukat / PDF</button>
            <button class="button secondary" type="button" data-schedule-image-export>Saglabāt attēlu</button>
            <?php if ($isAdmin): ?>
                <a class="button secondary" href="<?= e(url('/schedules/edit?id=' . $schedule['id'])) ?>">Labot</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($scheduleViewNotice)): ?>
    <section class="schedule-view-notice no-print">
        <?= e($scheduleViewNotice) ?>
    </section>
<?php endif; ?>

<section class="panel schedule-panel">
    <div class="mobile-week-nav no-print" data-week-nav>
        <?php foreach ($weekRanges as $index => $range): ?>
            <button class="week-tab <?= $index === 0 ? 'active' : '' ?>" type="button" data-week="<?= $index ?>">
                <?= $range[0] ?>-<?= $range[1] ?>
            </button>
        <?php endforeach; ?>
        <button class="week-tab" type="button" data-week="full">Pilns</button>
    </div>

    <div class="schedule-fit-wrap">
        <table class="schedule-grid full-month-view" data-full-month-table>
            <thead>
                <tr>
                    <th class="employee-col">Darbinieks</th>
                    <?php for ($day = 1; $day <= 31; $day++): ?>
                        <?php
                            $setting = $daySettings[$day] ?? null;
                            $class = '';
                            if ($setting) {
                                $class = in_array($setting['day_type'], ['saturday', 'sunday'], true)
                                    ? 'weekend'
                                    : $setting['day_type'];
                            }
                            $style = '';
                            if ($setting && in_array($setting['day_type'], ['saturday', 'sunday'], true)) {
                                $style = 'background: ' . e($setting['background_color'] ?: '#d7e0ea') . '; color: ' . e($setting['text_color'] ?: '#111827') . ';';
                            } elseif ($setting && ($setting['background_color'] || $setting['text_color'])) {
                                $style = 'background: ' . e($setting['background_color'] ?: 'inherit') . '; color: ' . e($setting['text_color'] ?: 'inherit') . ';';
                            }
                        ?>
                        <th class="day-col <?= e($class) ?>" style="<?= $style ?>"><?= $day ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedule['employees'])): ?>
                    <tr>
                        <td class="employee-col" colspan="32">Sim grafikam vel nav darbinieku.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($schedule['employees'] as $employee): ?>
                        <?php
                            $shiftsByDay = [];
                            foreach ($employee['shifts'] as $shift) {
                                $shiftsByDay[(int) $shift['day_number']] = $shift;
                            }
                        ?>
                        <tr>
                            <td class="employee-col"><?= e($employee['name']) ?></td>
                            <?php for ($day = 1; $day <= 31; $day++): ?>
                                <?php
                                    $shift = $shiftsByDay[$day] ?? null;
                                    $type = $shift ? ($shiftTypes[$shift['shift_code']] ?? null) : null;
                                    $setting = $daySettings[$day] ?? null;
                                    $class = '';
                                    if ($setting) {
                                        $class = in_array($setting['day_type'], ['saturday', 'sunday'], true)
                                            ? 'weekend'
                                            : $setting['day_type'];
                                    }
                                    $style = '';
                                    if ($setting && in_array($setting['day_type'], ['saturday', 'sunday'], true)) {
                                        $style = 'background: ' . e($setting['background_color'] ?: '#d7e0ea') . '; color: ' . e($setting['text_color'] ?: '#111827') . ';';
                                    } elseif ($setting && ($setting['background_color'] || $setting['text_color'])) {
                                        $style = 'background: ' . e($setting['background_color'] ?: 'inherit') . '; color: ' . e($setting['text_color'] ?: 'inherit') . ';';
                                    }
                                ?>
                                <td class="<?= e($class) ?>" style="<?= $style ?>">
                                    <?php if ($shift && $type): ?>
                                        <span class="shift-token" style="background: <?= e($type['background_color']) ?>; color: <?= e($type['text_color']) ?>;" title="<?= e($type['label']) ?>">
                                            <?= e($shift['shift_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mobile-week-wrap" data-weekly-schedule>
        <?php foreach ($weekRanges as $index => $range): ?>
            <div class="mobile-week <?= $index === 0 ? 'active' : '' ?>" data-week-panel="<?= $index ?>">
                <table class="schedule-grid mobile-week-table">
                    <thead>
                        <tr>
                            <th class="employee-col">Darbinieks</th>
                            <?php for ($day = $range[0]; $day <= $range[1]; $day++): ?>
                                <?php
                                    $setting = $daySettings[$day] ?? null;
                                    $class = '';
                                    if ($setting) {
                                        $class = in_array($setting['day_type'], ['saturday', 'sunday'], true)
                                            ? 'weekend'
                                            : $setting['day_type'];
                                    }
                                    $style = '';
                                    if ($setting && in_array($setting['day_type'], ['saturday', 'sunday'], true)) {
                                        $style = 'background: ' . e($setting['background_color'] ?: '#d7e0ea') . '; color: ' . e($setting['text_color'] ?: '#111827') . ';';
                                    } elseif ($setting && ($setting['background_color'] || $setting['text_color'])) {
                                        $style = 'background: ' . e($setting['background_color'] ?: 'inherit') . '; color: ' . e($setting['text_color'] ?: 'inherit') . ';';
                                    }
                                ?>
                                <th class="day-col <?= e($class) ?>" style="<?= $style ?>"><?= $day ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule['employees'] as $employee): ?>
                            <?php
                                $shiftsByDay = [];
                                foreach ($employee['shifts'] as $shift) {
                                    $shiftsByDay[(int) $shift['day_number']] = $shift;
                                }
                            ?>
                            <tr>
                                <td class="employee-col"><?= e($employee['name']) ?></td>
                                <?php for ($day = $range[0]; $day <= $range[1]; $day++): ?>
                                    <?php
                                        $shift = $shiftsByDay[$day] ?? null;
                                        $type = $shift ? ($shiftTypes[$shift['shift_code']] ?? null) : null;
                                        $setting = $daySettings[$day] ?? null;
                                        $class = '';
                                        if ($setting) {
                                            $class = in_array($setting['day_type'], ['saturday', 'sunday'], true)
                                                ? 'weekend'
                                                : $setting['day_type'];
                                        }
                                        $style = '';
                                        if ($setting && in_array($setting['day_type'], ['saturday', 'sunday'], true)) {
                                            $style = 'background: ' . e($setting['background_color'] ?: '#d7e0ea') . '; color: ' . e($setting['text_color'] ?: '#111827') . ';';
                                        } elseif ($setting && ($setting['background_color'] || $setting['text_color'])) {
                                            $style = 'background: ' . e($setting['background_color'] ?: 'inherit') . '; color: ' . e($setting['text_color'] ?: 'inherit') . ';';
                                        }
                                    ?>
                                    <td class="<?= e($class) ?>" style="<?= $style ?>">
                                        <?php if ($shift && $type): ?>
                                            <span class="shift-token" style="background: <?= e($type['background_color']) ?>; color: <?= e($type['text_color']) ?>;" title="<?= e($type['label']) ?>">
                                                <?= e($shift['shift_code']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!empty($holidays)): ?>
    <section class="panel">
        <h2>Svetku dienas</h2>
        <div class="holiday-list">
            <?php foreach ($holidays as $day => $holiday): ?>
                <div class="holiday-item">
                    <span class="holiday-color-dot" style="background: <?= e($holiday['background_color'] ?: '#fde68a') ?>;"></span>
                    <strong><?= e((string) $day) ?>.</strong>
                    <span><?= e($holiday['label'] ?: 'Svetku diena') ?></span>
                    <?php if ($isAdmin): ?>
                        <a class="link-button no-print" href="<?= e(url('/schedules/edit?id=' . $schedule['id'])) ?>">L</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($canViewHoursSummary): ?>
    <section class="panel">
        <h2>Stundu kopsavilkums</h2>
        <?php if (empty($schedule['employees'])): ?>
            <p>Nav darbinieku.</p>
        <?php else: ?>
            <div class="summary-grid">
                <?php foreach ($schedule['employees'] as $employee): ?>
                    <div class="summary-chip">
                        <strong><?= e($employee['name']) ?></strong>
                        <span><?= e((string) $employee['shifts_count']) ?> mainas</span>
                        <span><?= e((string) $employee['hours']) ?> h</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel schedule-legend-panel">
    <h2>Legenda</h2>
    <div class="legend-grid">
        <?php foreach ($shiftTypes as $type): ?>
            <div class="legend-item">
                <span class="shift-token" style="background: <?= e($type['background_color']) ?>; color: <?= e($type['text_color']) ?>;">
                    <?= e($type['code']) ?>
                </span>
                <span><?= e($type['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
