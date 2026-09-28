<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel" style="max-width: 420px; margin: 48px auto;">
    <h1>Pieslēgšanās</h1>
    <form method="post" action="<?= e(url('/login')) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <label for="username">Lietotājvārds</label>
            <input id="username" name="username" type="text" autocomplete="username" required>
        </div>
        <div class="form-row">
            <label for="password">Parole</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button" type="submit">Pieslēgties</button>
    </form>
</section>
