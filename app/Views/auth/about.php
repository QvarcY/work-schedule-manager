<?php

/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

$appName = trim((string) env_value('APP_NAME', 'Work Schedule Manager'));

if ($appName === '') {
    $appName = 'Work Schedule Manager';
}

?>

<section class="panel" style="max-width: 980px; margin: 32px auto;">
    <div class="page-title-row">
        <div>
            <h1><?= e($appName) ?></h1>
            <p class="muted">
                Darba grafiku izveide, pārvaldība un komandas informēšana vienuviet.
            </p>
        </div>

        <div class="toolbar no-print">
            <a class="button secondary" href="<?= e(url('/login')) ?>">
                Pieslēgties
            </a>
        </div>
    </div>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2>Par sistēmu</h2>

    <p>
        <?= e($appName) ?> ir pašmitināma darba grafiku pārvaldības sistēma
        komandām, kurām nepieciešams veidot, publicēt un pārskatīt maiņu grafikus.
    </p>

    <p>
        Sistēma apvieno grafiku plānošanu, darbinieku piekļuves,
        izmaiņu uzskaiti un paziņojumus vienā tīmekļa lietotnē,
        kas paredzēta lietošanai gan datorā, gan mobilajās ierīcēs.
    </p>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2>Galvenās iespējas</h2>

    <div class="feature-grid">
        <article class="feature-card">
            <h3>Darba grafiki</h3>
            <p>Veido, rediģē, publicē un arhivē maiņu grafikus.</p>
        </article>

        <article class="feature-card">
            <h3>Maiņu veidi</h3>
            <p>Definē darba laiku, krāsas un citus maiņu parametrus.</p>
        </article>

        <article class="feature-card">
            <h3>Darbinieku profili</h3>
            <p>Pārvaldi darbiniekus, viņu profilus un grafika redzamību.</p>
        </article>

        <article class="feature-card">
            <h3>Lomas un tiesības</h3>
            <p>Kontrolē piekļuvi sistēmas funkcijām un informācijai.</p>
        </article>

        <article class="feature-card">
            <h3>Brīvdienu pieteikumi</h3>
            <p>Darbinieki var iesniegt vēlamos brīvdienu datumus.</p>
        </article>

        <article class="feature-card">
            <h3>Grafiku apliecinājumi</h3>
            <p>Uzskaiti, kuri darbinieki ir iepazinušies ar publicēto grafiku.</p>
        </article>

        <article class="feature-card">
            <h3>Paziņojumi</h3>
            <p>Informē lietotājus par grafiku publikācijām un izmaiņām.</p>
        </article>

        <article class="feature-card">
            <h3>Darbību vēsture</h3>
            <p>Saglabā administratoru un lietotāju svarīgāko darbību uzskaiti.</p>
        </article>
    </div>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2>Modulāra arhitektūra</h2>

    <p>
        Papildu funkcionalitāte ir sadalīta moduļos, kurus iespējams
        aktivizēt vai deaktivizēt atbilstoši konkrētās instalācijas vajadzībām.
    </p>

    <p>
        Tādējādi pamatfunkcionalitāti iespējams saglabāt vienkāršu,
        bet sistēmu paplašināt pēc nepieciešamības.
    </p>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 18px;">
    <h2>Piekļuve</h2>

    <p>
        Lietotāju konti tiek veidoti kontrolētā veidā.
        Ja ir aktivizēts ielūgumu modulis, administrators var ģenerēt
        vienreiz izmantojamu ielūguma saiti jaunam lietotājam.
    </p>
</section>

<section class="panel" style="max-width: 980px; margin: 0 auto 32px;">
    <h2>Pašmitināšana</h2>

    <p>
        <?= e($appName) ?> paredzēts izvietošanai uz sava PHP un MySQL/MariaDB
        servera. Instalācijas konfigurācija tiek glabāta lokālā
        <code>.env</code> failā, kas nav jāpublicē versiju kontroles sistēmā.
    </p>
</section>

<section
    id="project-attribution"
    class="panel"
    style="max-width: 980px; margin: 0 auto 32px;"
>
    <h2>Autors, pirmkods un licence</h2>

    <p>
        <strong>Original project by QvarcY.</strong>
        Work Schedule Manager oriģinālo publisko versiju izveidoja
        un publicēja QvarcY.
    </p>

    <p>
        <a
            href="https://github.com/QvarcY"
            target="_blank"
            rel="noopener noreferrer"
        >QvarcY GitHub</a>
        ·
        <a
            href="https://github.com/QvarcY/work-schedule-manager"
            target="_blank"
            rel="noopener noreferrer"
        >Source code</a>
        ·
        <a
            href="https://buymeacoffee.com/craftin"
            target="_blank"
            rel="noopener noreferrer"
        >Buy Me a Coffee</a>
    </p>

    <p>
        Programmatūra tiek izplatīta saskaņā ar
        <a
            href="https://www.gnu.org/licenses/agpl-3.0.html"
            target="_blank"
            rel="license noopener noreferrer"
        >GNU AGPL-3.0-or-later</a>.
        Pilnais licences teksts un papildu AGPL 7. sadaļas nosacījumi
        atrodas pirmkoda failos <code>LICENSE</code>,
        <code>NOTICE.md</code> un <code>ADDITIONAL_TERMS.md</code>.
    </p>

    <p class="muted">
        Modificētas versijas drīkst izmantot un izplatīt atbilstoši
        licences noteikumiem, taču tās nedrīkst maldinoši uzdot par
        QvarcY oriģinālo, nemodificēto versiju, un ir jāsaglabā
        piemērojamā autora atsauce.
    </p>

    <p class="muted">
        Programmatūra tiek nodrošināta bez garantijas tādā apjomā,
        kā noteikts GNU AGPL v3.
    </p>
</section>
