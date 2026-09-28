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
        return 'Pievienots grafikam';
    }

    if ($changeType === 'employee_removed') {
        return 'Izņemts no grafika';
    }

    $oldCode = strtoupper(trim((string) ($item['old_code'] ?? '')));
    $newCode = strtoupper(trim((string) ($item['new_code'] ?? '')));
    $oldKind = $markerKind($oldCode);
    $newKind = $markerKind($newCode);

    if ($oldKind === 'regular_day_off' && $newKind !== 'regular_day_off') {
        return match ($newKind) {
            'mandatory_day_off' => 'Atzīmēta obligātā brīvdiena',
            'vacation' => 'Pievienota atvaļinājuma diena',
            'sick_day' => 'Pievienota slimības diena',
            default => 'Pievienota maiņa',
        };
    }

    if ($oldKind !== 'regular_day_off' && $newKind === 'regular_day_off') {
        return match ($oldKind) {
            'mandatory_day_off' => 'Noņemta obligātā brīvdiena',
            'vacation' => 'Noņemta atvaļinājuma diena',
            'sick_day' => 'Noņemta slimības diena',
            default => 'Noņemta maiņa',
        };
    }

    if ($oldKind === 'mandatory_day_off' && $newKind === 'shift') {
        return 'Obligātā brīvdiena nomainīta uz maiņu';
    }

    if ($oldKind === 'shift' && $newKind === 'mandatory_day_off') {
        return 'Maiņa nomainīta uz obligāto brīvdienu';
    }

    if ($oldKind === 'shift' && $newKind === 'shift') {
        return 'Mainīta maiņa';
    }

    return 'Mainīts marķējums';
};
$code = static function (?string $value): string {
    $value = strtoupper(trim((string) $value));

    if ($value === '') {
        return 'Parasta brīvdiena';
    }

    $labels = [
        'S' => 'Slimības diena',
        'A' => 'Atvaļinājuma diena',
        'N' => 'Nakts maiņa',
        'D*' => 'Dienas maiņu vadītājs',
        'D' => 'Dienas maiņa',
        'X' => 'Obligāta brīvdiena',
        'N*' => 'Nakts maiņu vadītājs',
        'DT' => 'Dienas maiņa',
        'NT' => 'Nakts maiņa',
        'DT*' => 'Dienas maiņu vadītājs',
        'NT*' => 'Nakts maiņu vadītājs',
    ];

    return isset($labels[$value]) ? $value . ' - ' . $labels[$value] : $value;
};
$codeClass = static function (?string $value): string {
    $value = strtoupper(trim((string) $value));

    return $value === '' ? 'empty' : strtolower(str_replace('*', '-leader', $value));
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
            <h1>Grafika izmaiņas</h1>
            <p class="muted">Šeit redzamas izmaiņas, kas attiecas uz tavām grafikā piesaistītajām maiņām.</p>
        </div>
    </div>
</section>

<?php if (empty($batches)): ?>
    <section class="panel">
        <p>Tev pašlaik nav reģistrētu grafika izmaiņu.</p>
    </section>
<?php else: ?>
    <div class="notification-list">
        <?php foreach ($batches as $batch): ?>
            <?php $items = $itemsByBatch[(int) $batch['id']] ?? []; ?>
            <article class="notification-card type-schedule <?= (int) ($batch['is_read'] ?? 0) === 1 ? '' : 'unread' ?>">
                <div class="page-title-row" style="gap:12px; align-items:flex-start;">
                    <div>
                        <strong><?= e($batch['schedule_name']) ?></strong>
                        <small><?= e((string) $batch['created_at']) ?> · <?= e($batch['month'] ?? '') ?> · <?= e((string) ($batch['summary'] ?? '')) ?></small>
                    </div>
                    <?php if ((int) ($batch['is_read'] ?? 0) !== 1): ?>
                        <form method="post" action="<?= e(url('/schedule-changes/read')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="batch_id" value="<?= e((string) $batch['id']) ?>">
                            <button class="button secondary" type="submit">Apskatīts</button>
                        </form>
                    <?php endif; ?>
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
