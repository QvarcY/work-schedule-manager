<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel invite-accept-panel">
    <h1><?= e(t('invitations.submitted.title')) ?></h1>
    <p><?= e(t('invitations.submitted.description')) ?></p>
    <p class="muted"><?= e(t('invitations.submitted.hint')) ?></p>
    <a class="button secondary" href="<?= e(url('/login')) ?>"><?= e(t('invitations.back_to_login')) ?></a>
</section>
