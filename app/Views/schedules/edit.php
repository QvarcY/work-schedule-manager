<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$days = range(1, 31);
$daySettings = [];
$weekendDays = [];
$holidaySettings = [];
foreach ($schedule['day_settings'] as $setting) {
    $daySettings[(int) $setting['day_number']] = $setting;
    if (in_array($setting['day_type'], ['saturday', 'sunday'], true)) {
        $weekendDays[] = (int) $setting['day_number'];
    }
    if ($setting['day_type'] === 'holiday') {
        $holidaySettings[(int) $setting['day_number']] = $setting;
    }
}

$employees = $schedule['employees'];
$registeredEmployees = $registeredEmployees ?? [];
if (empty($employees)) {
    $employees = array_fill(0, 5, [
        'name' => '',
        'shifts_count' => 0,
        'hours' => 0,
        'shifts' => [],
    ]);
}
?>

<section class="panel">
    <div class="page-title-row">
        <div>
            <h1><?= e(t('schedules.edit.title')) ?></h1>
            <p class="muted"><?= e($schedule['schedule_name']) ?> · <?= e($schedule['month']) ?></p>
        </div>
        <div class="toolbar no-print">
            <button class="button" type="submit" form="schedule-editor-form"><?= e(t('common.save')) ?></button>
            <a class="button secondary" href="<?= e(url('/schedules/show?id=' . $schedule['id'])) ?>"><?= e(t('common.view')) ?></a>
            <form method="post" action="<?= e(url('/schedules/publish')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="schedule_id" value="<?= e((string) $schedule['id']) ?>">
                <button class="button secondary" type="submit"><?= e(t('schedules.publish.submit')) ?></button>
            </form>
            <form method="post" action="<?= e(url('/schedules/delete')) ?>" data-confirm="<?= e(t('schedules.delete.confirm')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="schedule_id" value="<?= e((string) $schedule['id']) ?>">
                <button class="button danger" type="submit"><?= e(t('common.delete')) ?></button>
            </form>
        </div>
    </div>
</section>

<section class="panel">
    <form id="schedule-editor-form" method="post" action="<?= e(url('/schedules/update')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="schedule_id" value="<?= e((string) $schedule['id']) ?>">
        <input type="hidden" id="employees_json" name="employees_json" value="">
        <input type="hidden" id="day_settings_json" name="day_settings_json" value="">

        <div class="editor-meta-grid">
            <div class="form-row">
                <label for="schedule_name"><?= e(t('schedules.fields.name')) ?></label>
                <input id="schedule_name" name="schedule_name" type="text" value="<?= e($schedule['schedule_name']) ?>" required>
            </div>
            <div class="form-row">
                <label for="month"><?= e(t('schedules.fields.month')) ?></label>
                <input id="month" name="month" type="text" value="<?= e($schedule['month']) ?>" required>
            </div>
            <div class="form-row">
                <label for="status"><?= e(t('schedules.fields.status')) ?></label>
                <select id="status" name="status">
                    <?php foreach (['draft' => t('schedule.status.draft'), 'published' => t('schedule.status.published'), 'archived' => t('schedule.status.archived')] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $schedule['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="editor-section">
            <div class="section-heading">
                <div>
                    <h2><?= e(t('schedules.editor.table')) ?></h2>
                    <p class="muted"><?= e(t('schedules.editor.instructions')) ?></p>
                </div>
                <div class="toolbar no-print">
                    <select id="registered-employee-select" class="compact-select">
                        <option value=""><?= e(empty($registeredEmployees) ? t('schedules.editor.no_registered_employees') : t('schedules.editor.registered_employee')) ?></option>
                        <?php if (!empty($registeredEmployees)): ?>
                            <?php foreach ($registeredEmployees as $registeredEmployee): ?>
                                <?php
                                    $firstName = trim((string) ($registeredEmployee['first_name'] ?? ''));
                                    $lastName = trim((string) ($registeredEmployee['last_name'] ?? ''));
                                    $lastInitial = '';
                                    if ($lastName !== '') {
                                        $lastInitial = (function_exists('mb_substr') ? mb_substr($lastName, 0, 1) : substr($lastName, 0, 1)) . '.';
                                    }
                                    $displayName = trim($firstName . ' ' . $lastInitial);
                                    $displayName = $displayName !== '' ? $displayName : (string) $registeredEmployee['username'];
                                    $fullName = trim($firstName . ' ' . $lastName);
                                    $fullName = $fullName !== '' ? $fullName : (string) $registeredEmployee['username'];
                                ?>
                                <option
                                    value="<?= e((string) $registeredEmployee['id']) ?>"
                                    data-display-name="<?= e($displayName) ?>"
                                    data-full-name="<?= e($fullName) ?>"
                                ><?= e($fullName) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (!empty($registeredEmployees)): ?>
                        <button class="button secondary" type="button" id="add-registered-employee"><?= e(t('schedules.editor.add_from_list')) ?></button>
                    <?php endif; ?>
                    <button class="button secondary" type="button" id="add-employee-row"><?= e(t('schedules.editor.add_employee')) ?></button>
                    <button class="button secondary" type="button" id="toggle-compact-schedule"><?= e(t('schedules.editor.compact_view')) ?></button>
                </div>
            </div>

            <div class="table-wrap editor-table-wrap">
                <table class="schedule-grid editor-grid" id="schedule-editor" data-shift-types='<?= e(json_encode($shiftTypes, JSON_UNESCAPED_UNICODE)) ?>'>
                    <thead>
                        <tr>
                            <th class="employee-col"><?= e(t('schedules.employee')) ?></th>
                            <?php foreach ($days as $day): ?>
                                <?php
                                    $setting = $daySettings[$day] ?? null;
                                    $isWeekend = in_array($day, $weekendDays, true);
                                    $isHoliday = isset($holidaySettings[$day]);
                                    $style = $isHoliday
                                        ? 'background: ' . e($holidaySettings[$day]['background_color'] ?: '#fde68a') . '; color: ' . e($holidaySettings[$day]['text_color'] ?: '#111827') . ';'
                                        : '';
                                ?>
                                <th
                                    class="day-col clickable-day <?= $isWeekend ? 'weekend-header' : '' ?> <?= $isHoliday ? 'holiday-header' : '' ?>"
                                    data-day="<?= $day ?>"
                                    data-weekend="<?= $isWeekend ? '1' : '0' ?>"
                                    style="<?= $style ?>"
                                    title="<?= e(t('schedules.editor.toggle_day_off')) ?>"
                                ><?= $day ?></th>
                            <?php endforeach; ?>
                            <th class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <?php
                                $shiftsByDay = [];
                                foreach ($employee['shifts'] ?? [] as $shift) {
                                    $shiftsByDay[(int) $shift['day_number']] = $shift;
                                }
                            ?>
                            <tr class="employee-editor-row">
                                <td class="employee-col">
                                    <input class="employee-user-id-input" type="hidden" value="<?= e((string) ($employee['user_id'] ?? '')) ?>">
                                    <input class="employee-name-input" type="text" value="<?= e($employee['name'] ?? '') ?>" placeholder="<?= e(t('users.fields.first_name')) ?>">
                                    <select class="employee-link-select" aria-label="<?= e(t('schedules.editor.link_employee')) ?>">
                                        <option value=""><?= e(empty($registeredEmployees) ? t('schedules.editor.no_accounts') : t('schedules.editor.not_linked')) ?></option>
                                        <?php if (!empty($registeredEmployees)): ?>
                                            <?php foreach ($registeredEmployees as $registeredEmployee): ?>
                                                <?php
                                                    $firstName = trim((string) ($registeredEmployee['first_name'] ?? ''));
                                                    $lastName = trim((string) ($registeredEmployee['last_name'] ?? ''));
                                                    $lastInitial = '';
                                                    if ($lastName !== '') {
                                                        $lastInitial = (function_exists('mb_substr') ? mb_substr($lastName, 0, 1) : substr($lastName, 0, 1)) . '.';
                                                    }
                                                    $displayName = trim($firstName . ' ' . $lastInitial);
                                                    $displayName = $displayName !== '' ? $displayName : (string) $registeredEmployee['username'];
                                                    $fullName = trim($firstName . ' ' . $lastName);
                                                    $fullName = $fullName !== '' ? $fullName : (string) $registeredEmployee['username'];
                                                ?>
                                                <option
                                                    value="<?= e((string) $registeredEmployee['id']) ?>"
                                                    data-display-name="<?= e($displayName) ?>"
                                                    <?= (string) ($employee['user_id'] ?? '') === (string) $registeredEmployee['id'] ? 'selected' : '' ?>
                                                ><?= e($fullName) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <?php foreach ($days as $day): ?>
                                    <?php $shift = $shiftsByDay[$day] ?? null; ?>
                                <td class="<?= in_array($day, $weekendDays, true) ? 'weekend' : '' ?> <?= isset($holidaySettings[$day]) ? 'holiday' : '' ?>">
                                    <input class="shift-editor-input" type="text" maxlength="4" value="<?= e($shift['shift_code'] ?? '') ?>" data-day="<?= $day ?>">
                                </td>
                                <?php endforeach; ?>
                                <td class="summary-shifts" hidden><?= e((string) ($employee['shifts_count'] ?? 0)) ?></td>
                                <td class="summary-hours" hidden><?= e((string) ($employee['hours'] ?? 0)) ?></td>
                                <td class="no-print"><button class="link-button remove-employee-row" type="button"><?= e(t('common.delete')) ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="editor-section holiday-editor">
            <div class="section-heading">
                <div>
                    <h2><?= e(t('schedules.holidays')) ?></h2>
                    <p class="muted"><?= e(t('schedules.editor.holiday_instructions')) ?></p>
                </div>
            </div>

            <div class="holiday-add-row no-print">
                <select id="holiday-day">
                    <?php foreach ($days as $day): ?>
                        <option value="<?= $day ?>"><?= $day ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="holiday-color">
                    <option value="#fde68a|#111827"><?= e(t('colors.yellow')) ?></option>
                    <option value="#fecaca|#7f1d1d"><?= e(t('colors.red')) ?></option>
                    <option value="#bbf7d0|#14532d"><?= e(t('colors.green')) ?></option>
                    <option value="#bfdbfe|#1e3a8a"><?= e(t('colors.blue')) ?></option>
                    <option value="#ddd6fe|#4c1d95"><?= e(t('colors.violet')) ?></option>
                    <option value="#fed7aa|#7c2d12"><?= e(t('colors.orange')) ?></option>
                    <option value="#e5e7eb|#111827"><?= e(t('colors.gray')) ?></option>
                    <option value="#fbcfe8|#831843"><?= e(t('colors.pink')) ?></option>
                    <option value="#ccfbf1|#134e4a"><?= e(t('colors.turquoise')) ?></option>
                    <option value="#fef3c7|#78350f"><?= e(t('colors.light_yellow')) ?></option>
                </select>
                <input id="holiday-name" type="text" placeholder="<?= e(t('schedules.fields.name')) ?>">
                <button class="button secondary" type="button" id="add-holiday"><?= e(t('common.add')) ?></button>
            </div>

            <div class="holiday-list" id="holiday-list">
                <?php foreach ($holidaySettings as $day => $holiday): ?>
                    <div class="holiday-item" data-day="<?= $day ?>" data-bg="<?= e($holiday['background_color'] ?: '#fde68a') ?>" data-text="<?= e($holiday['text_color'] ?: '#111827') ?>">
                        <span class="holiday-color-dot" style="background: <?= e($holiday['background_color'] ?: '#fde68a') ?>;"></span>
                        <strong><?= $day ?>.</strong>
                        <span class="holiday-name"><?= e($holiday['label'] ?: t('schedules.holiday_default')) ?></span>
                        <button class="link-button holiday-edit" type="button"><?= e(t('common.edit_short')) ?></button>
                        <button class="link-button holiday-remove" type="button" aria-label="<?= e(t('common.delete')) ?>">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</section>

<section class="panel">
    <h2><?= e(t('schedules.hours_summary')) ?></h2>
    <div class="summary-grid" id="editor-summary"></div>
</section>

<section class="panel schedule-legend-panel">
    <h2><?= e(t('schedules.legend')) ?></h2>
    <div class="legend-grid">
        <?php foreach ($shiftTypes as $type): ?>
            <div class="legend-item">
                <span class="shift-token" style="background: <?= e($type['background_color']) ?>; color: <?= e($type['text_color']) ?>;">
                    <?= e($type['code']) ?>
                </span>
                <span><?= e($type['label']) ?>, <?= e((string) $type['hours']) ?> h</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
