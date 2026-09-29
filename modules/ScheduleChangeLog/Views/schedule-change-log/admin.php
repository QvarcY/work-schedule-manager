<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$markerKind = static function (?string $value): string {
    $value = strtoupper(trim((string) $value));

    return match ($value) {
        '' => 'regular_day_off',
        'X' => 'mandatory_day_off',
        'A' => 'vacation',
        'S' => 'sick_day',
        default => 'shift',
    };
};
$labelForChange = static function (array $item) use ($markerKind): string {
    $changeType = (string) ($item['change_type'] ?? '');

    if ($changeType === 'employee_added') {
        return t('schedule_changes.change.employee_added');
    }

    if ($changeType === 'employee_removed') {
        return t('schedule_changes.change.employee_removed');
    }

    $oldCode = strtoupper(trim((string) ($item['old_code'] ?? '')));
    $newCode = strtoupper(trim((string) ($item['new_code'] ?? '')));
    $oldKind = $markerKind($oldCode);
    $newKind = $markerKind($newCode);

    if ($oldKind === 'regular_day_off' && $newKind !== 'regular_day_off') {
        return match ($newKind) {
            'mandatory_day_off' => t('schedule_changes.change.mandatory_day_off_added'),
            'vacation' => t('schedule_changes.change.vacation_added'),
            'sick_day' => t('schedule_changes.change.sick_day_added'),
            default => t('schedule_changes.change.shift_added'),
        };
    }

    if ($oldKind !== 'regular_day_off' && $newKind === 'regular_day_off') {
        return match ($oldKind) {
            'mandatory_day_off' => t('schedule_changes.change.mandatory_day_off_removed'),
            'vacation' => t('schedule_changes.change.vacation_removed'),
            'sick_day' => t('schedule_changes.change.sick_day_removed'),
            default => t('schedule_changes.change.shift_removed'),
        };
    }

    if ($oldKind === 'mandatory_day_off' && $newKind === 'shift') {
        return t('schedule_changes.change.day_off_to_shift');
    }

    if ($oldKind === 'shift' && $newKind === 'mandatory_day_off') {
        return t('schedule_changes.change.shift_to_day_off');
    }

    if ($oldKind === 'shift' && $newKind === 'shift') {
        return t('schedule_changes.change.shift_changed');
    }

    return t('schedule_changes.change.marker_changed');
};
$code = static function (?string $value): string {
    $value = strtoupper(trim((string) $value));

    if ($value === '') {
        return t('schedule_changes.codes.regular_day_off');
    }

    $labels = [
        'S' => t('schedule_changes.codes.sick_day'),
        'A' => t('schedule_changes.codes.vacation'),
        'N' => t('schedule_changes.codes.night_shift'),
        'D*' => t('schedule_changes.codes.day_leader'),
        'D' => t('schedule_changes.codes.day_shift'),
        'X' => t('schedule_changes.codes.mandatory_day_off'),
        'N*' => t('schedule_changes.codes.night_leader'),
        'DT' => t('schedule_changes.codes.day_shift'),
        'NT' => t('schedule_changes.codes.night_shift'),
        'DT*' => t('schedule_changes.codes.day_leader'),
        'NT*' => t('schedule_changes.codes.night_leader'),
    ];

    return isset($labels[$value]) ? $value . ' - ' . $labels[$value] : $value;
};
$codeClass = static function (?string $value): string {
    $value = strtoupper(trim((string) $value));

    return $value === '' ? 'empty' : strtolower(str_replace('*', '-leader', $value));
};$userLabel = static function (array $user): string {
    $name = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
    return $name !== '' ? $name : (string) ($user['username'] ?? '');
};
?>

<style>
    .schedule-change-items {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    .schedule-change-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid #d8e2ec;
        border-radius: 8px;
        background: #f8fafc;
        align-items: start;
    }

    .schedule-change-day {
        min-width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #dff4f1;
        color: #0f766e;
        font-weight: 800;
    }

    .schedule-change-employee {
        color: #172033;
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .schedule-change-title {
        margin-top: 2px;
        font-weight: 800;
        line-height: 1.2;
    }

    .schedule-change-flow {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
        margin-top: 7px;
        color: #46586b;
    }

    .schedule-change-code {
        display: inline-flex;
        max-width: 100%;
        align-items: center;
        min-height: 28px;
        padding: 3px 8px;
        border-radius: 6px;
        border: 1px solid #cad6e1;
        background: #ffffff;
        color: #172033;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .schedule-change-code.d,
    .schedule-change-code.dt {
        border-color: #f59e0b;
        background: #fff7df;
    }

    .schedule-change-code.n,
    .schedule-change-code.nt {
        border-color: #2563eb;
        background: #eff6ff;
    }

    .schedule-change-code.d-leader,
    .schedule-change-code.dt-leader,
    .schedule-change-code.n-leader,
    .schedule-change-code.nt-leader {
        border-color: #facc15;
        background: #fef3c7;
    }

    .schedule-change-code.a {
        border-color: #22c55e;
        background: #ecfdf3;
    }

    .schedule-change-code.s {
        border-color: #94a3b8;
        background: #f1f5f9;
    }

    .schedule-change-code.x {
        border-color: #0f766e;
        background: #e6fffb;
    }

    .schedule-change-code.empty {
        border-style: dashed;
        color: #64748b;
        font-weight: 650;
    }

    .schedule-change-arrow {
        color: #64748b;
        font-weight: 800;
    }

    @media (max-width: 680px) {
        .schedule-change-items {
            grid-template-columns: 1fr;
        }

        .schedule-change-item {
            padding: 10px;
        }
    }
</style>
<section class="panel">
    <div class="page-title-row">
        <div>
            <h1><?= e(t('schedule_changes.admin.title')) ?></h1>
            <p class="muted"><?= e(t('schedule_changes.admin.description')) ?></p>
        </div>
    </div>
</section>

<section class="panel">
    <form method="get" action="<?= e(url('/schedule-changes/admin')) ?>" class="journal-filter-grid">
        <div>
            <label><?= e(t('schedule_changes.admin.schedule')) ?></label>
            <select name="schedule_id">
                <option value="0"><?= e(t('schedule_changes.admin.all_schedules')) ?></option>
                <?php foreach ($schedules as $schedule): ?>
                    <option value="<?= e((string) $schedule['id']) ?>" <?= (int) $selectedScheduleId === (int) $schedule['id'] ? 'selected' : '' ?>>
                        <?= e($schedule['schedule_name']) ?><?= !empty($schedule['month']) ? ' · ' . e($schedule['month']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><?= e(t('schedules.employee')) ?></label>
            <select name="user_id">
                <option value="0"><?= e(t('schedule_changes.admin.all_employees')) ?></option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= e((string) $user['id']) ?>" <?= (int) $selectedUserId === (int) $user['id'] ? 'selected' : '' ?>>
                        <?= e($userLabel($user)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="journal-filter-actions">
            <button class="button" type="submit"><?= e(t('common.filter')) ?></button>
            <a class="button secondary" href="<?= e(url('/schedule-changes/admin')) ?>"><?= e(t('common.clear')) ?></a>
        </div>
    </form>
</section>

<?php if (empty($batches)): ?>
    <section class="panel">
        <p><?= e(t('schedule_changes.empty')) ?></p>
    </section>
<?php else: ?>
    <div class="notification-list">
        <?php foreach ($batches as $batch): ?>
            <?php $items = $itemsByBatch[(int) $batch['id']] ?? []; ?>
            <article class="notification-card type-schedule">
                <div>
                    <strong><?= e($batch['schedule_name']) ?></strong>
                    <small>
                        <?= e((string) $batch['created_at']) ?> · <?= e($batch['month'] ?? '') ?> · <?= e(t('schedule_changes.summary', ['changes' => $batch['item_count'] ?? 0, 'employees' => $batch['affected_users'] ?? 0])) ?>
                    </small>
                </div>

                <div class="schedule-change-items">
                    <?php foreach ($items as $item): ?>
                        <div class="schedule-change-item">
                            <div class="schedule-change-day">
                                <?= $item['day_number'] ? e((string) $item['day_number']) . '.' : 'Info' ?>
                            </div>
                            <div>
                                <div class="schedule-change-employee"><?= e($item['employee_name']) ?></div>
                                <div class="schedule-change-title"><?= e($labelForChange($item)) ?></div>
                                <?php if ($item['day_number']): ?>
                                    <div class="schedule-change-flow">
                                        <span class="schedule-change-code <?= e($codeClass($item['old_code'] ?? null)) ?>"><?= e($code($item['old_code'] ?? null)) ?></span>
                                        <span class="schedule-change-arrow">→</span>
                                        <span class="schedule-change-code <?= e($codeClass($item['new_code'] ?? null)) ?>"><?= e($code($item['new_code'] ?? null)) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
