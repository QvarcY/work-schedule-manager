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
use App\Models\ShiftType;
use App\Services\ActivityLogger;

final class ShiftTypeController
{
    public function index(): void
    {
        auth()->requireAdmin();

        view('shift-types/index', [
            'title' => t('shift_types.title'),
            'shiftTypes' => (new ShiftType())->all(),
        ]);
    }

    public function store(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        (new ShiftType())->create($_POST);
        ActivityLogger::log('shift_type_created', 'shift_type', null, (string) ($_POST['code'] ?? ''));
        Session::flash('success', t('shift_types.create.success'));
        redirect('/shift-types');
    }

    public function update(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['id'] ?? 0);
        (new ShiftType())->update($id, $_POST);
        ActivityLogger::log('shift_type_updated', 'shift_type', $id, (string) ($_POST['code'] ?? ''));
        Session::flash('success', t('shift_types.update.success'));
        redirect('/shift-types');
    }

    public function delete(): void
    {
        auth()->requireAdmin();
        Csrf::verify($_POST['_csrf'] ?? null);

        $id = (int) ($_POST['id'] ?? 0);
        $deleted = (new ShiftType())->delete($id);
        if ($deleted) {
            ActivityLogger::log('shift_type_deleted', 'shift_type', $id, 'shift type deleted');
            Session::flash('success', t('shift_types.delete.success'));
        } else {
            Session::flash('error', t('shift_types.delete.in_use'));
        }
        redirect('/shift-types');
    }
}
