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
 * File documentation for amd/src/download_ui.js.
 *
 * @package   local_coursedownloads
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later
import {init as initSearchBar} from 'local_coursedownloads/search_bar';
import {init as initSelection} from 'local_coursedownloads/file_selection';

/** Shared live GET filters with independent, current-result-only exports. */
export const init = () => {
    const scope = document.getElementById('local-coursedownloads-browser');
    const form = scope?.querySelector('form[data-search-bar]');
    if (!form || form.dataset.coursedownloadsReady) return;
    form.dataset.coursedownloadsReady = '1';
    const sectionBoxes = () => [...form.querySelectorAll('input[name="sections[]"]')];
    const summary = () => {
        const count = sectionBoxes().filter(box => box.checked).length;
        const label = form.querySelector('[data-coursedownloads-section-summary]');
        if (label) label.textContent = count ? form.dataset.coursedownloadsSelectedFormat.replace('__COUNT__', count)
            : form.dataset.coursedownloadsAllSections;
    };
    const pending = () => {
        scope.dataset.filterPending = '1';
        scope.querySelectorAll('[data-coursedownloads-export]').forEach(button => { button.disabled = true; });
        summary();
    };
    form.addEventListener('change', event => {
        if (event.target.matches('select,input[type="checkbox"]')) pending();
    }, true);
    form.addEventListener('submit', () => { if (form.classList.contains('local-plugin-search-bar-live')) pending(); }, true);
    form.addEventListener('click', event => {
        const special = event.target.closest('[data-coursedownloads-remove-section],[data-coursedownloads-clear-sections]');
        if (special && event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            event.preventDefault(); event.stopPropagation();
            for (const box of sectionBoxes()) {
                if (special.hasAttribute('data-coursedownloads-clear-sections') || box.value === special.dataset.coursedownloadsRemoveSection) {
                    box.checked = false;
                }
            }
            (sectionBoxes()[0] || form.querySelector('select'))?.dispatchEvent(new Event('change', {bubbles: true}));
            return;
        }
        if (event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey
                && event.target.closest('[data-search-bar-reset],[data-search-bar-chips] a')) pending();
    }, true);
    form.addEventListener('search-bar-updated', () => {
        scope.dataset.filterPending = '0';
        summary();
        const settings = document.querySelector('.local_coursedownloads_settings_form');
        if (settings) {
            const url = new URL(form.action);
            url.search = new URLSearchParams(new FormData(form)).toString();
            settings.setAttribute('action', url.href);
        }
        initSelection();
    });
    window.addEventListener('popstate', pending);
    initSearchBar(form);
    initSelection();
    summary();
};
