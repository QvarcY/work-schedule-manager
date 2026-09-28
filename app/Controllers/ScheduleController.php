<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Models\Schedule;
use App\Models\ShiftType;
use App\Models\User;
use App\Services\ActivityLogger;

final class ScheduleController
{
    public function index(): void
    {
        $user = auth()->requireLogin();
        ActivityLogger::log('schedules_list_viewed', 'schedule', null, 'Apskatīts grafiku saraksts.', $user);
        $isAdmin = ($user['role'] ?? '') === 'admin';
        $scheduleModel = new Schedule();

        view('schedules/index', [
            'title' => 'Grafiki',
            'schedules' => $isAdmin ? $scheduleModel->all() : $scheduleModel->published(),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(): void
    {
        $user = auth()->requireLogin();

        $id = (int) ($_GET['id'] ?? 0);
        $schedule = (new Schedule())->findWithEmployees($id);

        if (!$schedule) {
            Session::flash('error', 'Grafiks nav atrasts.');
            redirect('/schedules');
        }

        if (($user['role'] ?? '') !== 'admin' && ($schedule['status'] ?? '') !== 'published') {
            Session::flash('error', 'Šis grafiks vēl nav publicēts.');
            redirect('/schedules');
        }

        $viewMode = $this->scheduleViewMode($user);
        $viewNotice = null;
        if (($user['role'] ?? '') !== 'admin') {
            [$schedule, $viewNotice] = $this->filterScheduleForUser($schedule, (int) $user['id'], $viewMode);
        }

        ActivityLogger::log('schedule_viewed', 'schedule', $id, (string) $schedule['schedule_name'], $user);

        view('schedules/show', [
            'title' => $schedule['schedule_name'],
            'schedule' => $schedule,
            'shiftTypes' => (new ShiftType())->activeByCode(),
            'scheduleViewMode' => $viewMode,
            'scheduleViewNotice' => $viewNotice,
        ]);
    }

    public function edit(): void
    {
        auth()->requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $schedule = (new Schedule())->findWithEmployees($id);

        if (!$schedule) {
            Session::flash('error', 'Grafiks nav atrasts.');
            redirect('/schedules');
        }

        view('schedules/edit', [
            'title' => 'Labot grafiku',
            'schedule' => $schedule,
            'shiftTypes' => (new ShiftType())->activeByCode(),
            'registeredEmployees' => (new User())->employees(),
        ]);
    }

    public function create(): void
    {
        auth()->requireAdmin();

        view('schedules/create', [
            'title' => 'Jauns grafiks',
            'shiftTypes' => (new ShiftType())->activeByCode(),
        ]);
    }

    public function store(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $name = trim((string) ($_POST['schedule_name'] ?? ''));
        $month = trim((string) ($_POST['month'] ?? ''));

        if ($name === '' || $month === '') {
            Session::flash('error', 'Grafika nosaukums un mēnesis ir obligāti.');
            redirect('/schedules/create');
        }

        $id = (new Schedule())->create($name, $month);
        ActivityLogger::log('schedule_created', 'schedule', $id, $name . ' / ' . $month);
        Session::flash('success', 'Grafika sagatave izveidota.');
        redirect('/schedules/edit?id=' . $id);
    }

    public function update(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['schedule_id'] ?? 0);
        $name = trim((string) ($_POST['schedule_name'] ?? ''));
        $month = trim((string) ($_POST['month'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        $employees = json_decode((string) ($_POST['employees_json'] ?? '[]'), true);
        $daySettings = json_decode((string) ($_POST['day_settings_json'] ?? '[]'), true);

        if ($id <= 0 || $name === '' || $month === '' || !is_array($employees) || !is_array($daySettings)) {
            Session::flash('error', 'Grafika dati nav korekti.');
            redirect('/schedules');
        }

        $scheduleModel = new Schedule();
        $oldSchedule = $scheduleModel->findWithEmployees($id);

        $scheduleModel->saveEditorData($id, $name, $month, $status, $employees, $daySettings);
        ActivityLogger::log('schedule_updated', 'schedule', $id, $name . ' / ' . $month . ' / ' . $status);
        if ($status === 'published') {
            $newSchedule = $scheduleModel->findWithEmployees($id);
            if ($oldSchedule && ($oldSchedule['status'] ?? '') === 'published' && $newSchedule) {
                $this->recordScheduleChanges($oldSchedule, $newSchedule);
                $this->notifyChangedScheduleEmployees($oldSchedule, $newSchedule);
            } else {
                $this->notifyAssignedEmployees($id, 'published');
            }
        }
        Session::flash('success', 'Grafiks saglabāts.');
        redirect('/schedules/edit?id=' . $id);
    }

    public function publish(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['schedule_id'] ?? 0);
        (new Schedule())->setStatus($id, 'published');
        ActivityLogger::log('schedule_published', 'schedule', $id, 'Grafiks publicēts.');
        $this->notifyAssignedEmployees($id, 'published');

        Session::flash('success', 'Grafiks publicēts.');
        redirect('/schedules/show?id=' . $id);
    }

    public function delete(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['schedule_id'] ?? 0);
        (new Schedule())->delete($id);
        ActivityLogger::log('schedule_deleted', 'schedule', $id, 'Grafiks dzēsts.');

        Session::flash('success', 'Grafiks dzēsts.');
        redirect('/schedules');
    }

    private function recordScheduleChanges(array $before, array $after): void
    {
        if (!class_exists('\Modules\ScheduleChangeLog\Services\ScheduleChangeLogger')) {
            return;
        }

        try {
            \Modules\ScheduleChangeLog\Services\ScheduleChangeLogger::recordIfEnabled(
                $before,
                $after,
                (int) ($_SESSION['user_id'] ?? 0) ?: null
            );
        } catch (\Throwable) {
            // Change logging must not block schedule saving.
        }
    }
    private function notifyAssignedEmployees(int $scheduleId, string $event): void
    {
        if (!class_exists('\Modules\Notifications\NotificationService')) {
            return;
        }

        try {
            $schedule = (new Schedule())->findWithEmployees($scheduleId);
            if (!$schedule) {
                return;
            }

            $userIds = [];
            foreach ($schedule['employees'] as $employee) {
                $userId = (int) ($employee['user_id'] ?? 0);
                if ($userId > 0) {
                    $userIds[] = $userId;
                }
            }

            $userIds = array_values(array_unique($userIds));
            if (empty($userIds)) {
                return;
            }

            $title = $event === 'updated' ? 'Grafiks atjaunināts' : 'Publicēts jauns grafiks';
            $body = sprintf(
                "%s: %s / %s. Atver grafiku sistēmu, lai apskatītu aktuālo informāciju.",
                $title,
                (string) $schedule['schedule_name'],
                (string) $schedule['month']
            );

            (new \Modules\Notifications\NotificationService())->createForUsers(
                $title,
                $body,
                $userIds,
                'schedule',
                (int) ($_SESSION['user_id'] ?? 0) ?: null
            );
        } catch (\Throwable) {
            // Notification delivery must not block schedule publishing or saving.
        }
    }

    private function notifyChangedScheduleEmployees(array $before, array $after): void
    {
        if (!class_exists('\Modules\Notifications\NotificationService')) {
            return;
        }

        try {
            $assignedUserIds = $this->assignedUserIds($after);
            $affectedUserIds = $this->changedScheduleUserIds($before, $after);

            if (empty($affectedUserIds)) {
                $affectedUserIds = $assignedUserIds;
            }

            $subscriberUserIds = [];
            try {
                $subscriberUserIds = $this->allScheduleUpdateSubscriberIds($assignedUserIds);
            } catch (\Throwable) {
                $subscriberUserIds = [];
            }

            $userIds = array_values(array_unique(array_merge($affectedUserIds, $subscriberUserIds)));

            if (empty($userIds)) {
                return;
            }

            $title = 'Grafiks atjaunināts';
            $body = sprintf(
                "%s: %s / %s. Atver grafiku sistēmu, lai apskatītu aktuālo informāciju.",
                $title,
                (string) ($after['schedule_name'] ?? ''),
                (string) ($after['month'] ?? '')
            );

            (new \Modules\Notifications\NotificationService())->createForUsers(
                $title,
                $body,
                $userIds,
                'schedule',
                (int) ($_SESSION['user_id'] ?? 0) ?: null
            );
        } catch (\Throwable) {
            // Notification delivery must not block schedule saving.
        }
    }

    private function changedScheduleUserIds(array $before, array $after): array
    {
        $beforeMap = $this->scheduleEmployeeSnapshotsByUser($before);
        $afterMap = $this->scheduleEmployeeSnapshotsByUser($after);
        $userIds = array_values(array_unique(array_merge(array_keys($beforeMap), array_keys($afterMap))));
        $changed = [];

        foreach ($userIds as $userId) {
            if (($beforeMap[$userId] ?? null) !== ($afterMap[$userId] ?? null)) {
                $changed[] = (int) $userId;
            }
        }

        return $changed;
    }

    private function scheduleEmployeeSnapshotsByUser(array $schedule): array
    {
        $snapshots = [];

        foreach (($schedule['employees'] ?? []) as $employee) {
            $userId = (int) ($employee['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $shifts = [];
            foreach (($employee['shifts'] ?? []) as $shift) {
                $day = (int) ($shift['day_number'] ?? 0);
                if ($day < 1 || $day > 31) {
                    continue;
                }

                $shifts[$day] = strtoupper(trim((string) ($shift['shift_code'] ?? '')));
            }
            ksort($shifts);

            $snapshots[$userId][] = [
                'name' => trim((string) ($employee['name'] ?? '')),
                'shifts' => $shifts,
            ];
        }

        foreach ($snapshots as &$rows) {
            usort($rows, static function (array $left, array $right): int {
                return json_encode($left, JSON_UNESCAPED_UNICODE) <=> json_encode($right, JSON_UNESCAPED_UNICODE);
            });
        }
        unset($rows);

        return $snapshots;
    }

    private function assignedUserIds(array $schedule): array
    {
        $userIds = [];
        foreach (($schedule['employees'] ?? []) as $employee) {
            $userId = (int) ($employee['user_id'] ?? 0);
            if ($userId > 0) {
                $userIds[] = $userId;
            }
        }

        return array_values(array_unique($userIds));
    }

    private function allScheduleUpdateSubscriberIds(array $candidateUserIds): array
    {
        $candidateUserIds = array_values(array_unique(array_filter(array_map('intval', $candidateUserIds))));
        if (empty($candidateUserIds)) {
            return [];
        }

        (new \App\Services\AccessControl())->ensureSchema();
        $placeholders = implode(',', array_fill(0, count($candidateUserIds), '?'));
        $stmt = Database::connection()->prepare("SELECT id FROM users WHERE receive_all_schedule_updates = 1 AND id IN ($placeholders)");
        $stmt->execute($candidateUserIds);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }
    private function scheduleViewMode(array $user): string
    {
        $mode = (string) ($user['schedule_view_mode'] ?? 'full');

        return in_array($mode, ['full', 'own', 'day', 'night'], true) ? $mode : 'full';
    }

    private function filterScheduleForUser(array $schedule, int $userId, string $mode): array
    {
        if ($mode === 'full') {
            return [$schedule, null];
        }

        $labels = [
            'own' => 'Tu redzi tikai savas maiņas.',
            'day' => 'Tu redzi tikai dienas maiņu grafiku.',
            'night' => 'Tu redzi tikai nakts maiņu grafiku.',
        ];

        $filteredEmployees = [];
        foreach ($schedule['employees'] as $employee) {
            if ($mode === 'own' && (int) ($employee['user_id'] ?? 0) !== $userId) {
                continue;
            }

            $employee['shifts'] = array_values(array_filter($employee['shifts'], function (array $shift) use ($mode): bool {
                $code = strtoupper((string) ($shift['shift_code'] ?? ''));
                if ($mode === 'day') {
                    return str_starts_with($code, 'D');
                }
                if ($mode === 'night') {
                    return str_starts_with($code, 'N');
                }

                return true;
            }));

            if ($mode !== 'own' && empty($employee['shifts'])) {
                continue;
            }

            [$employee['shifts_count'], $employee['hours']] = $this->totalsForVisibleShifts($employee['shifts']);
            $filteredEmployees[] = $employee;
        }

        $schedule['employees'] = $filteredEmployees;

        return [$schedule, $labels[$mode] ?? null];
    }

    private function totalsForVisibleShifts(array $shifts): array
    {
        $shiftTypes = (new ShiftType())->activeByCode();
        $shiftCount = 0;
        $hours = 0;

        foreach ($shifts as $shift) {
            $code = (string) ($shift['shift_code'] ?? '');
            if ($code === '') {
                continue;
            }

            $shiftCount++;
            $hours += (int) ($shiftTypes[$code]['hours'] ?? 0);
        }

        return [$shiftCount, $hours];
    }
}
