<?php
/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */
?>
<section class="panel">
    <h1><?= e((string) ($statusCode ?? 500)) ?> — <?= e($errorTitle ?? t('errors.title')) ?></h1>
    <p><?= e($message ?? t('errors.fallback')) ?></p>
    <a class="button secondary" href="<?= e(url('/')) ?>"><?= e(t('common.back')) ?></a>
</section>
