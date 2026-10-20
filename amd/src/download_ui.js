// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later
import {init as initSearchBar} from 'local_coursefiles/search_bar';
import {init as initSelection} from 'local_coursefiles/file_selection';

/** Shared live GET filters with independent, current-result-only exports. */
export const init = () => {
    const scope = document.getElementById('local-coursefiles-browser');
    const form = scope?.querySelector('form[data-search-bar]');
    if (!form || form.dataset.coursefilesReady) return;
    form.dataset.coursefilesReady = '1';
    const sectionBoxes = () => [...form.querySelectorAll('input[name="sections[]"]')];
    const summary = () => {
        const count = sectionBoxes().filter(box => box.checked).length;
        const label = form.querySelector('[data-coursefiles-section-summary]');
        if (label) label.textContent = count ? form.dataset.coursefilesSelectedFormat.replace('__COUNT__', count)
            : form.dataset.coursefilesAllSections;
    };
    const pending = () => {
        scope.dataset.filterPending = '1';
        scope.querySelectorAll('[data-coursefiles-export]').forEach(button => { button.disabled = true; });
        summary();
    };
    form.addEventListener('change', event => {
        if (event.target.matches('select,input[type="checkbox"]')) pending();
    }, true);
    form.addEventListener('submit', () => { if (form.classList.contains('local-plugin-search-bar-live')) pending(); }, true);
    form.addEventListener('click', event => {
        const special = event.target.closest('[data-coursefiles-remove-section],[data-coursefiles-clear-sections]');
        if (special && event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            event.preventDefault(); event.stopPropagation();
            for (const box of sectionBoxes()) {
                if (special.hasAttribute('data-coursefiles-clear-sections') || box.value === special.dataset.coursefilesRemoveSection) {
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
        const settings = document.querySelector('.local_coursefiles_settings_form');
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
