/*
 * Work Schedule Manager
 * Copyright (C) 2026 QvarcY
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * Additional terms: see ADDITIONAL_TERMS.md
 */

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('[data-confirm]')) {
        return;
    }

    const message = form.getAttribute('data-confirm');
    if (!window.confirm(message)) {
        event.preventDefault();
    }
});

function toggleAppNav(button) {
    const appNav = document.querySelector('[data-app-nav]');
    if (!button || !appNav) return;

    const isOpen = appNav.classList.toggle('open');
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        toggleAppNav(button);
    });
});

document.addEventListener('click', (event) => {
    const button = event.target.closest?.('[data-nav-toggle]');
    if (!button) return;

    event.preventDefault();
    toggleAppNav(button);
});

const editor = document.getElementById('schedule-editor');
const editorForm = document.getElementById('schedule-editor-form');

if (editor && editorForm) {
    const shiftTypes = JSON.parse(editor.dataset.shiftTypes || '{}');
    const tbody = editor.querySelector('tbody');
    const summary = document.getElementById('editor-summary');
    const holidayList = document.getElementById('holiday-list');
    const holidays = new Map();

    holidayList?.querySelectorAll('.holiday-item').forEach((item) => {
        holidays.set(Number(item.dataset.day), {
            day: Number(item.dataset.day),
            label: item.querySelector('.holiday-name')?.textContent.trim() || 'Svetku diena',
            background_color: item.dataset.bg || '#fde68a',
            text_color: item.dataset.text || '#111827',
        });
    });

    const renderSummary = () => {
        if (!summary) return;

        const items = [];
        tbody.querySelectorAll('.employee-editor-row').forEach((row) => {
            const name = row.querySelector('.employee-name-input').value.trim();
            if (!name) return;

            items.push(`
                <div class="summary-chip">
                    <strong>${name}</strong>
                    <span>${row.querySelector('.summary-shifts').textContent} mainas</span>
                    <span>${row.querySelector('.summary-hours').textContent} h</span>
                </div>
            `);
        });

        summary.innerHTML = items.length ? items.join('') : '<p class="muted">Nav aizpilditu darbinieku.</p>';
    };

    const applyShiftStyle = (input) => {
        const code = input.value.trim().toUpperCase();
        const type = shiftTypes[code];

        input.value = code;
        if (type) {
            input.style.backgroundColor = type.background_color;
            input.style.color = type.text_color;
        } else {
            input.style.removeProperty('background-color');
            input.style.removeProperty('color');
        }
        input.title = type ? `${type.label} (${type.hours} h)` : '';
    };

    const recalculateRow = (row) => {
        let shifts = 0;
        let hours = 0;

        row.querySelectorAll('.shift-editor-input').forEach((input) => {
            applyShiftStyle(input);
            const type = shiftTypes[input.value.trim().toUpperCase()];
            if (!type) return;

            if (Number(type.counts_as_shift) === 1) shifts += 1;
            hours += Number(type.hours || 0);
        });

        row.querySelector('.summary-shifts').textContent = String(shifts);
        row.querySelector('.summary-hours').textContent = String(hours);
        renderSummary();
    };

    const applyDayClasses = (day, isWeekend, holiday = null) => {
        const header = editor.querySelector(`.clickable-day[data-day="${day}"]`);
        const inputs = editor.querySelectorAll(`.shift-editor-input[data-day="${day}"]`);

        if (header) {
            header.dataset.weekend = isWeekend ? '1' : '0';
            header.classList.toggle('weekend-header', isWeekend);
            header.classList.toggle('holiday-header', Boolean(holiday));
            header.style.backgroundColor = holiday ? holiday.background_color : '';
            header.style.color = holiday ? holiday.text_color : '';
        }

        inputs.forEach((input) => {
            const cell = input.closest('td');
            if (!cell) return;
            cell.classList.toggle('weekend', isWeekend);
            cell.classList.toggle('holiday', Boolean(holiday));
            cell.style.backgroundColor = holiday ? holiday.background_color : '';
            cell.style.color = holiday ? holiday.text_color : '';

            if (!shiftTypes[input.value.trim().toUpperCase()]) {
                input.style.removeProperty('background-color');
                input.style.removeProperty('color');
            }
        });
    };

    const renderHolidays = () => {
        if (!holidayList) return;

        holidayList.innerHTML = '';
        Array.from(holidays.values()).sort((a, b) => a.day - b.day).forEach((holiday) => {
            const item = document.createElement('div');
            item.className = 'holiday-item';
            item.dataset.day = String(holiday.day);
            item.dataset.bg = holiday.background_color;
            item.dataset.text = holiday.text_color;
            item.innerHTML = `
                <span class="holiday-color-dot" style="background: ${holiday.background_color};"></span>
                <strong>${holiday.day}.</strong>
                <span class="holiday-name">${holiday.label}</span>
                <button class="link-button holiday-edit" type="button">L</button>
                <button class="link-button holiday-remove" type="button">x</button>
            `;

            item.querySelector('.holiday-remove').addEventListener('click', () => {
                holidays.delete(holiday.day);
                const header = editor.querySelector(`.clickable-day[data-day="${holiday.day}"]`);
                applyDayClasses(holiday.day, header?.dataset.weekend === '1', null);
                renderHolidays();
            });

            item.querySelector('.holiday-edit').addEventListener('click', () => {
                document.getElementById('holiday-day').value = String(holiday.day);
                document.getElementById('holiday-name').value = holiday.label;
                document.getElementById('holiday-color').value = `${holiday.background_color}|${holiday.text_color}`;
            });

            holidayList.appendChild(item);
        });
    };

    const bindRow = (row) => {
        row.querySelectorAll('.shift-editor-input').forEach((input) => {
            input.addEventListener('input', () => recalculateRow(row));
            applyShiftStyle(input);
        });

        row.querySelector('.employee-name-input')?.addEventListener('input', renderSummary);
        const linkSelect = row.querySelector('.employee-link-select');
        if (linkSelect) {
            linkSelect.dataset.previousValue = row.querySelector('.employee-user-id-input')?.value || '';
            linkSelect.addEventListener('change', () => {
                const hiddenInput = row.querySelector('.employee-user-id-input');
                const nameInput = row.querySelector('.employee-name-input');
                const userId = linkSelect.value;
                const alreadyLinked = userId && Array.from(tbody.querySelectorAll('.employee-editor-row'))
                    .some((otherRow) => otherRow !== row && otherRow.querySelector('.employee-user-id-input')?.value === userId);

                if (alreadyLinked) {
                    window.alert('Šis darbinieks grafikā jau ir piesaistīts citai rindai.');
                    linkSelect.value = linkSelect.dataset.previousValue || '';
                    return;
                }

                if (hiddenInput) hiddenInput.value = userId;
                linkSelect.dataset.previousValue = userId;
                if (userId && nameInput) {
                    const option = linkSelect.options[linkSelect.selectedIndex];
                    nameInput.value = option?.dataset.displayName || option?.textContent.trim() || nameInput.value;
                }
                renderSummary();
            });
        }
        row.querySelector('.remove-employee-row')?.addEventListener('click', () => {
            row.remove();
            renderSummary();
        });

        recalculateRow(row);
    };

    const createRow = (employee = {}) => {
        const row = document.createElement('tr');
        row.className = 'employee-editor-row';

        const userId = employee.user_id ? String(employee.user_id) : '';
        const employeeName = employee.name || '';
        const registeredSelect = document.getElementById('registered-employee-select');
        let linkSelect = '';
        if (registeredSelect) {
            const options = Array.from(registeredSelect.options).map((option) => {
                const value = option.value || '';
                const label = value ? option.textContent.trim() : 'Nav piesaistīts kontam';
                const displayName = option.dataset.displayName || '';
                const selected = value === userId ? ' selected' : '';
                return `<option value="${escapeHtml(value)}" data-display-name="${escapeHtml(displayName)}"${selected}>${escapeHtml(label)}</option>`;
            }).join('');
            linkSelect = `<select class="employee-link-select" aria-label="Piesaistīt reģistrētam darbiniekam">${options}</select>`;
        }

        let html = `
            <td class="employee-col">
                <input class="employee-user-id-input" type="hidden" value="${escapeHtml(userId)}">
                <input class="employee-name-input" type="text" value="${escapeHtml(employeeName)}" placeholder="Vards">
                ${linkSelect}
            </td>
        `;
        for (let day = 1; day <= 31; day += 1) {
            html += `<td><input class="shift-editor-input" type="text" maxlength="4" data-day="${day}"></td>`;
        }
        html += '<td class="summary-shifts" hidden>0</td><td class="summary-hours" hidden>0</td><td class="no-print"><button class="link-button remove-employee-row" type="button">Dzest</button></td>';
        row.innerHTML = html;

        tbody.appendChild(row);
        bindRow(row);
    };

    const addRegisteredEmployee = () => {
        const select = document.getElementById('registered-employee-select');
        if (!select) return;

        let option = select.options[select.selectedIndex] || null;
        if (!option || !option.value) {
            const employeeOptions = Array.from(select.options).filter((item) => item.value);
            if (employeeOptions.length === 1) {
                option = employeeOptions[0];
                select.value = option.value;
            }
        }

        const userId = option && option.value ? option.value : '';
        if (!userId) {
            window.alert('Izvēlies reģistrētu darbinieku no saraksta.');
            return;
        }

        const alreadyAdded = Array.from(tbody.querySelectorAll('.employee-user-id-input'))
            .some((input) => input.value === userId);

        if (alreadyAdded) {
            window.alert('Darbinieks jau ir pievienots grafikam.');
            return;
        }

        createRow({
            user_id: userId,
            name: option.dataset.displayName || option.textContent.trim(),
        });
        select.value = '';
    };

    document.getElementById('add-employee-row')?.addEventListener('click', () => createRow());
    document.getElementById('add-registered-employee')?.addEventListener('click', addRegisteredEmployee);
    document.getElementById('toggle-compact-schedule')?.addEventListener('click', () => {
        editor.classList.toggle('schedule-grid-compact');
    });

    editor.querySelectorAll('.clickable-day').forEach((header) => {
        header.addEventListener('click', () => {
            const day = Number(header.dataset.day);
            applyDayClasses(day, header.dataset.weekend !== '1', holidays.get(day) || null);
        });
    });

    document.getElementById('add-holiday')?.addEventListener('click', () => {
        const day = Number(document.getElementById('holiday-day').value);
        const [background, text] = document.getElementById('holiday-color').value.split('|');
        const label = document.getElementById('holiday-name').value.trim() || 'Svetku diena';

        holidays.set(day, { day, label, background_color: background, text_color: text });
        document.getElementById('holiday-name').value = '';

        const header = editor.querySelector(`.clickable-day[data-day="${day}"]`);
        applyDayClasses(day, header?.dataset.weekend === '1', holidays.get(day));
        renderHolidays();
    });

    tbody.querySelectorAll('.employee-editor-row').forEach(bindRow);
    holidays.forEach((holiday) => {
        const header = editor.querySelector(`.clickable-day[data-day="${holiday.day}"]`);
        applyDayClasses(holiday.day, header?.dataset.weekend === '1', holiday);
    });
    renderHolidays();
    renderSummary();

    editorForm.addEventListener('submit', () => {
        const employees = [];

        tbody.querySelectorAll('.employee-editor-row').forEach((row) => {
            const name = row.querySelector('.employee-name-input').value.trim();
            if (!name) return;
            const userId = row.querySelector('.employee-user-id-input')?.value || null;

            const shifts = {};
            row.querySelectorAll('.shift-editor-input').forEach((input) => {
                const code = input.value.trim().toUpperCase();
                if (code) shifts[input.dataset.day] = { code };
            });

            employees.push({ name, user_id: userId, shifts });
        });

        const daySettingsByDay = new Map();
        editor.querySelectorAll('.clickable-day').forEach((header) => {
            const day = Number(header.dataset.day);
            if (header.dataset.weekend === '1') {
                daySettingsByDay.set(day, {
                    day,
                    type: 'saturday',
                    label: 'Brivdiena',
                    background_color: '#d7e0ea',
                    text_color: '#111827',
                });
            }
        });

        holidays.forEach((holiday) => {
            daySettingsByDay.set(holiday.day, {
                day: holiday.day,
                type: 'holiday',
                label: holiday.label,
                background_color: holiday.background_color,
                text_color: holiday.text_color,
            });
        });

        document.getElementById('employees_json').value = JSON.stringify(employees);
        document.getElementById('day_settings_json').value = JSON.stringify(Array.from(daySettingsByDay.values()));
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const weekNav = document.querySelector('[data-week-nav]');
if (weekNav) {
    const tabs = weekNav.querySelectorAll('[data-week]');
    const panels = document.querySelectorAll('[data-week-panel]');
    const fullTableWrap = document.querySelector('[data-full-month-table]')?.closest('.schedule-fit-wrap');
    const weeklyWrap = document.querySelector('[data-weekly-schedule]');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const week = tab.dataset.week;

            tabs.forEach((item) => item.classList.toggle('active', item === tab));

            if (week === 'full') {
                fullTableWrap?.classList.add('mobile-force-full');
                weeklyWrap?.classList.add('hidden');
                return;
            }

            fullTableWrap?.classList.remove('mobile-force-full');
            weeklyWrap?.classList.remove('hidden');
            panels.forEach((panel) => {
                panel.classList.toggle('active', panel.dataset.weekPanel === week);
            });
        });
    });
}

const scheduleImageExportButton = document.querySelector('[data-schedule-image-export]');
if (scheduleImageExportButton) {
    scheduleImageExportButton.addEventListener('click', async () => {
        const originalLabel = scheduleImageExportButton.textContent;
        scheduleImageExportButton.disabled = true;
        scheduleImageExportButton.textContent = 'Gatavo attēlu...';

        try {
            await exportScheduleAsImage();
        } catch (error) {
            console.error(error);
            window.alert('Neizdevās izveidot attēlu. Pamēģini vēlreiz vai izmanto Drukāt / PDF opciju.');
        } finally {
            scheduleImageExportButton.disabled = false;
            scheduleImageExportButton.textContent = originalLabel;
        }
    });
}

async function exportScheduleAsImage() {
    const table = document.querySelector('[data-full-month-table]');
    if (!table) {
        throw new Error('Schedule table not found.');
    }

    const title = document.querySelector('.page-title-row h1')?.textContent.trim() || 'Grafiks';
    const meta = document.querySelector('.page-title-row .muted')?.textContent.trim() || '';
    const scheduleTable = cleanExportClone(table);
    const holidayPanel = findPanelByClassOrHeading('holiday-list', 'Svetku dienas');
    const summaryPanel = findPanelByClassOrHeading('summary-grid', 'Stundu kopsavilkums');
    const legendPanel = document.querySelector('.schedule-legend-panel');

    const blocks = [
        `<header class="export-header"><h1>${escapeHtml(title)}</h1>${meta ? `<p>${escapeHtml(meta)}</p>` : ''}</header>`,
        `<section class="export-panel export-schedule">${scheduleTable.outerHTML}</section>`,
    ];

    if (holidayPanel) {
        blocks.push(cleanExportClone(holidayPanel).outerHTML);
    }
    if (summaryPanel) {
        blocks.push(cleanExportClone(summaryPanel).outerHTML);
    }
    if (legendPanel) {
        blocks.push(cleanExportClone(legendPanel).outerHTML);
    }

    const width = Math.max(1180, Math.min(1700, Math.ceil(table.scrollWidth || table.getBoundingClientRect().width || 1180) + 56));
    const exportMarkup = buildScheduleImageMarkup(blocks.join(''), width);
    const measure = document.createElement('div');
    measure.style.cssText = `position:fixed;left:-20000px;top:0;width:${width}px;background:#fff;z-index:-1;`;
    measure.innerHTML = exportMarkup;
    document.body.appendChild(measure);
    const height = Math.max(560, Math.ceil(measure.scrollHeight));
    document.body.removeChild(measure);

    const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
<foreignObject width="100%" height="100%">
<div xmlns="http://www.w3.org/1999/xhtml">${exportMarkup}</div>
</foreignObject>
</svg>`;

    const imageUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
    try {
        const image = await loadImage(imageUrl);
        const scale = Math.min(2, Math.max(1, window.devicePixelRatio || 1));
        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(width * scale);
        canvas.height = Math.ceil(height * scale);
        const context = canvas.getContext('2d');
        context.scale(scale, scale);
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);
        context.drawImage(image, 0, 0, width, height);

        const blob = await canvasToBlob(canvas);
        const fileName = `${slugify(title || 'grafiks')}.png`;
        await saveBlobForDevice(blob, fileName, title);
    } finally {
        URL.revokeObjectURL(imageUrl);
    }
}

function findPanelByClassOrHeading(className, headingText) {
    const direct = document.querySelector(`.${className}`)?.closest('.panel');
    if (direct) return direct;

    return Array.from(document.querySelectorAll('section.panel')).find((panel) => {
        const heading = panel.querySelector('h2')?.textContent.trim() || '';
        return heading === headingText;
    }) || null;
}

function cleanExportClone(element) {
    const clone = element.cloneNode(true);
    clone.querySelectorAll('.no-print, button, form, input, select, textarea, script').forEach((node) => node.remove());
    clone.querySelectorAll('a').forEach((link) => {
        const replacement = document.createElement('span');
        replacement.innerHTML = link.innerHTML;
        replacement.className = link.className;
        link.replaceWith(replacement);
    });
    clone.querySelectorAll('[hidden]').forEach((node) => node.removeAttribute('hidden'));
    return clone;
}

function buildScheduleImageMarkup(content, width) {
    return `
<style>
*{box-sizing:border-box} .schedule-image-export{width:${width}px;background:#fff;color:#172033;font-family:Arial,Helvetica,sans-serif;padding:24px} .export-header{display:flex;justify-content:space-between;gap:20px;align-items:flex-end;margin-bottom:14px;border-bottom:2px solid #0f766e;padding-bottom:12px}.export-header h1{font-size:24px;line-height:1.15;margin:0;color:#111827}.export-header p{margin:6px 0 0;color:#526173;font-size:13px}.export-panel,.panel{border:1px solid #d8e1ea;border-radius:8px;background:#fff;padding:14px;margin:0 0 14px}.export-schedule{padding:10px}.panel h2{font-size:17px;margin:0 0 10px;color:#111827}.schedule-fit-wrap{overflow:visible}.mobile-week-wrap,.mobile-week-nav{display:none!important}.schedule-grid{width:100%;border-collapse:collapse;table-layout:fixed}.schedule-grid th,.schedule-grid td{border:1px solid #d6e0eb;text-align:center;padding:3px;font-size:11px;line-height:1.1;height:24px;vertical-align:middle}.schedule-grid th{background:#edf3f8;font-weight:700}.schedule-grid .employee-col{width:138px;min-width:138px;text-align:left;background:#e7eef5;font-weight:700;color:#101828}.schedule-grid .day-col{font-weight:700}.shift-token{display:inline-flex;align-items:center;justify-content:center;min-width:25px;min-height:18px;padding:2px 6px;border-radius:5px;font-weight:700;font-size:11px;line-height:1}.holiday-list{display:flex;flex-wrap:wrap;gap:8px}.holiday-item{display:inline-flex;align-items:center;gap:6px;border:1px solid #d8e1ea;border-radius:999px;padding:6px 10px;background:#f8fafc;font-size:12px}.holiday-color-dot{display:inline-block;width:12px;height:12px;border-radius:50%;border:1px solid rgba(17,24,39,.18)}.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:8px}.summary-chip{border:1px solid #d8e1ea;border-radius:8px;background:#f8fafc;padding:8px;display:flex;gap:8px;align-items:center;justify-content:space-between;font-size:12px}.summary-chip strong{font-size:13px}.legend-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:8px}.legend-item{display:flex;align-items:center;gap:8px;border:1px solid #d8e1ea;border-radius:8px;background:#f8fafc;padding:8px;font-size:12px}@media(max-width:700px){.schedule-image-export{padding:18px}.export-header{display:block}.export-header h1{font-size:20px}}
</style>
<div class="schedule-image-export">${content}</div>`;
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}

function canvasToBlob(canvas) {
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (blob) {
                resolve(blob);
                return;
            }
            reject(new Error('Canvas export failed.'));
        }, 'image/png');
    });
}

async function saveBlobForDevice(blob, fileName, title) {
    if (typeof File !== 'undefined' && navigator.canShare && navigator.share) {
        const file = new File([blob], fileName, { type: 'image/png' });
        if (navigator.canShare({ files: [file] })) {
            try {
                await navigator.share({ files: [file], title: title || 'Grafiks' });
                return;
            } catch (error) {
                if (error && error.name === 'AbortError') return;
            }
        }
    }

    downloadBlob(blob, fileName);
}

function downloadBlob(blob, fileName) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    link.remove();

    window.setTimeout(() => URL.revokeObjectURL(url), 1500);
}

function slugify(value) {
    return String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .toLowerCase()
        .slice(0, 80) || 'grafiks';
}

// Mobile-safe exporter: draws the schedule directly on canvas instead of using SVG foreignObject.
async function exportScheduleAsImage() {
    const table = document.querySelector('[data-full-month-table]');
    if (!table) throw new Error('Schedule table not found.');

    const title = document.querySelector('.page-title-row h1')?.textContent.trim() || 'Grafiks';
    const meta = document.querySelector('.page-title-row .muted')?.textContent.trim() || '';
    const rows = Array.from(table.tBodies[0]?.rows || []);
    const headers = Array.from(table.tHead?.rows[0]?.cells || []);
    const holidays = Array.from(document.querySelectorAll('.holiday-list .holiday-item'));
    const summaries = Array.from(document.querySelectorAll('.summary-grid .summary-chip'));
    const legends = Array.from(document.querySelectorAll('.schedule-legend-panel .legend-item'));

    const width = 1480;
    const padding = 28;
    const tableWidth = width - padding * 2;
    const employeeWidth = 160;
    const dayWidth = (tableWidth - employeeWidth) / 31;
    const headerHeight = 28;
    const rowHeight = 30;
    const sectionGap = 16;
    const holidayHeight = holidays.length ? 74 + Math.ceil(holidays.length / 4) * 32 : 0;
    const summaryHeight = summaries.length ? 74 + Math.ceil(summaries.length / 4) * 42 : 0;
    const legendHeight = legends.length ? 74 + Math.ceil(legends.length / 4) * 38 : 0;
    const height = padding + 58 + sectionGap + headerHeight + Math.max(rows.length, 1) * rowHeight + sectionGap + holidayHeight + summaryHeight + legendHeight + padding;
    const scale = Math.min(2, Math.max(1, window.devicePixelRatio || 1));
    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(width * scale);
    canvas.height = Math.ceil(height * scale);
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas is not available.');
    ctx.scale(scale, scale);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);

    let y = padding;
    ctx.fillStyle = '#111827';
    ctx.font = '700 26px Arial, Helvetica, sans-serif';
    ctx.fillText(title, padding, y + 24);
    if (meta) {
        ctx.fillStyle = '#526173';
        ctx.font = '13px Arial, Helvetica, sans-serif';
        ctx.fillText(meta, padding, y + 45);
    }
    ctx.fillStyle = '#0f766e';
    ctx.fillRect(padding, y + 54, tableWidth, 2);
    y += 72;

    drawScheduleTable(ctx, table, headers, rows, padding, y, tableWidth, employeeWidth, dayWidth, headerHeight, rowHeight);
    y += headerHeight + Math.max(rows.length, 1) * rowHeight + sectionGap;

    if (holidays.length) {
        y = drawExportSection(ctx, 'Svētku dienas', holidays.map((item) => ({
            label: item.textContent.replace(/\s+/g, ' ').trim(),
            color: item.querySelector('.holiday-color-dot') ? canvasColor(item.querySelector('.holiday-color-dot'), 'backgroundColor', '#fde68a') : '#fde68a',
        })), padding, y, tableWidth, 4, 'holiday') + sectionGap;
    }

    if (summaries.length) {
        y = drawExportSection(ctx, 'Stundu kopsavilkums', summaries.map((item) => ({
            label: item.textContent.replace(/\s+/g, ' ').trim(),
            color: '#0f766e',
        })), padding, y, tableWidth, 4, 'summary') + sectionGap;
    }

    if (legends.length) {
        y = drawExportSection(ctx, 'Leģenda', legends.map((item) => {
            const token = item.querySelector('.shift-token');
            const label = Array.from(item.childNodes).map((node) => node.textContent || '').join(' ').replace(/\s+/g, ' ').trim();
            return {
                label,
                code: token?.textContent.trim() || '',
                color: token ? canvasColor(token, 'backgroundColor', '#e5e7eb') : '#e5e7eb',
                textColor: token ? canvasColor(token, 'color', '#111827') : '#111827',
            };
        }), padding, y, tableWidth, 4, 'legend') + sectionGap;
    }

    const blob = await canvasToBlob(canvas);
    await saveBlobForDevice(blob, `${slugify(title || 'grafiks')}.png`, title);
}

function drawScheduleTable(ctx, table, headers, rows, x, y, tableWidth, employeeWidth, dayWidth, headerHeight, rowHeight) {
    drawPanelBackground(ctx, x, y, tableWidth, headerHeight + Math.max(rows.length, 1) * rowHeight);
    let cursorX = x;
    headers.forEach((header, index) => {
        const width = index === 0 ? employeeWidth : dayWidth;
        drawCell(ctx, cursorX, y, width, headerHeight, canvasColor(header, 'backgroundColor', index === 0 ? '#e7eef5' : '#edf3f8'), '#d6e0eb');
        ctx.fillStyle = canvasColor(header, 'color', '#111827');
        ctx.font = '700 12px Arial, Helvetica, sans-serif';
        ctx.textAlign = index === 0 ? 'left' : 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(header.textContent.trim(), index === 0 ? cursorX + 8 : cursorX + width / 2, y + headerHeight / 2);
        cursorX += width;
    });

    if (!rows.length) {
        drawCell(ctx, x, y + headerHeight, tableWidth, rowHeight, '#ffffff', '#d6e0eb');
        ctx.fillStyle = '#526173';
        ctx.font = '13px Arial, Helvetica, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText('Šim grafikam vēl nav darbinieku.', x + 8, y + headerHeight + rowHeight / 2);
        return;
    }

    rows.forEach((row, rowIndex) => {
        const rowY = y + headerHeight + rowIndex * rowHeight;
        Array.from(row.cells).forEach((cell, index) => {
            const cellX = index === 0 ? x : x + employeeWidth + (index - 1) * dayWidth;
            const width = index === 0 ? employeeWidth : dayWidth;
            const fallback = index === 0 ? '#f8fafc' : '#ffffff';
            drawCell(ctx, cellX, rowY, width, rowHeight, canvasColor(cell, 'backgroundColor', fallback), '#d6e0eb');

            if (index === 0) {
                ctx.fillStyle = '#111827';
                ctx.font = '13px Arial, Helvetica, sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(cell.textContent.trim(), cellX + 8, rowY + rowHeight / 2);
                return;
            }

            const token = cell.querySelector('.shift-token');
            if (token) {
                const tokenText = token.textContent.trim();
                const tokenW = Math.min(width - 6, Math.max(24, ctx.measureText(tokenText).width + 14));
                const tokenH = 20;
                const tokenX = cellX + (width - tokenW) / 2;
                const tokenY = rowY + (rowHeight - tokenH) / 2;
                drawRoundRect(ctx, tokenX, tokenY, tokenW, tokenH, 5, canvasColor(token, 'backgroundColor', '#e5e7eb'), null);
                ctx.fillStyle = canvasColor(token, 'color', '#111827');
                ctx.font = '700 12px Arial, Helvetica, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(tokenText, cellX + width / 2, rowY + rowHeight / 2);
            }
        });
    });
}

function drawExportSection(ctx, title, items, x, y, width, columns, type) {
    const itemHeight = type === 'summary' ? 34 : 30;
    const rows = Math.ceil(items.length / columns);
    const height = 44 + rows * itemHeight + 16;
    drawPanelBackground(ctx, x, y, width, height);
    ctx.fillStyle = '#111827';
    ctx.font = '700 17px Arial, Helvetica, sans-serif';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillText(title, x + 14, y + 27);

    const gap = 10;
    const itemWidth = (width - 28 - gap * (columns - 1)) / columns;
    items.forEach((item, index) => {
        const col = index % columns;
        const row = Math.floor(index / columns);
        const itemX = x + 14 + col * (itemWidth + gap);
        const itemY = y + 42 + row * itemHeight;
        drawRoundRect(ctx, itemX, itemY, itemWidth, itemHeight - 6, 7, '#f8fafc', '#d8e1ea');
        if (type === 'legend' && item.code) {
            drawRoundRect(ctx, itemX + 8, itemY + 6, 34, 18, 5, item.color, null);
            ctx.fillStyle = item.textColor || '#111827';
            ctx.font = '700 11px Arial, Helvetica, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(item.code, itemX + 25, itemY + 15);
            ctx.fillStyle = '#172033';
            ctx.font = '12px Arial, Helvetica, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(trimCanvasText(ctx, item.label, itemWidth - 54), itemX + 50, itemY + 16);
            return;
        }

        ctx.fillStyle = item.color || '#0f766e';
        ctx.beginPath();
        ctx.arc(itemX + 17, itemY + (itemHeight - 6) / 2, 5, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#172033';
        ctx.font = '12px Arial, Helvetica, sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(trimCanvasText(ctx, item.label, itemWidth - 34), itemX + 30, itemY + (itemHeight - 6) / 2);
    });

    return y + height;
}

function drawPanelBackground(ctx, x, y, width, height) {
    drawRoundRect(ctx, x, y, width, height, 8, '#ffffff', '#d8e1ea');
}

function drawCell(ctx, x, y, width, height, fill, stroke) {
    ctx.fillStyle = fill;
    ctx.fillRect(x, y, width, height);
    ctx.strokeStyle = stroke;
    ctx.lineWidth = 1;
    ctx.strokeRect(x, y, width, height);
}

function drawRoundRect(ctx, x, y, width, height, radius, fill, stroke) {
    const r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + width - r, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + r);
    ctx.lineTo(x + width, y + height - r);
    ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
    ctx.lineTo(x + r, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
    if (fill) {
        ctx.fillStyle = fill;
        ctx.fill();
    }
    if (stroke) {
        ctx.strokeStyle = stroke;
        ctx.lineWidth = 1;
        ctx.stroke();
    }
}

function canvasColor(element, property, fallback) {
    if (!element) return fallback;
    const value = window.getComputedStyle(element)[property];
    if (!value || value === 'transparent' || value === 'rgba(0, 0, 0, 0)') return fallback;
    return value;
}

function trimCanvasText(ctx, text, maxWidth) {
    const clean = String(text || '').replace(/\s+/g, ' ').trim();
    if (ctx.measureText(clean).width <= maxWidth) return clean;
    let value = clean;
    while (value.length > 1 && ctx.measureText(`${value}...`).width > maxWidth) {
        value = value.slice(0, -1);
    }
    return `${value}...`;
}
