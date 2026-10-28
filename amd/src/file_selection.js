/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 * File documentation for amd/src/file_selection.js.
 *
 * @package   local_coursedownloads
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later
/** Selection is local to the current result list and independent of section exports. */
export const init = () => {
    const form = document.getElementById('filelist');
    if (!form || form.dataset.selectionReady) return;
    form.dataset.selectionReady = '1';
    const all = form.querySelector('.local_coursedownloads_check_uncheck_all');
    const boxes = [...form.querySelectorAll('.local_coursedownloads_file_checkbox')];
    const button = form.querySelector('[data-coursedownloads-file-download]');
    const counter = form.querySelector('[data-coursedownloads-selection-count]');
    const refresh = () => {
        const count = boxes.filter(box => box.checked).length;
        if (all) { all.checked = boxes.length > 0 && count === boxes.length; all.indeterminate = count > 0 && count < boxes.length; }
        if (counter) { counter.hidden = false; counter.textContent = counter.dataset.format.replace('__COUNT__', count); }
        if (button) {
            button.hidden = false;
            button.disabled = count === 0 || form.closest('[data-search-bar-scope]')?.dataset.filterPending === '1';
            button.textContent = button.dataset.format.replace('__COUNT__', count);
        }
    };
    all?.addEventListener('change', () => { boxes.forEach(box => { box.checked = all.checked; }); refresh(); });
    boxes.forEach(box => box.addEventListener('change', refresh));
    refresh();
};
