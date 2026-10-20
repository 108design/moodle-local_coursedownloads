// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later
/** Selection is local to the current result list and independent of section exports. */
export const init = () => {
    const form = document.getElementById('filelist');
    if (!form || form.dataset.selectionReady) return;
    form.dataset.selectionReady = '1';
    const all = form.querySelector('.local_coursefiles_check_uncheck_all');
    const boxes = [...form.querySelectorAll('.local_coursefiles_file_checkbox')];
    const button = form.querySelector('[data-coursefiles-file-download]');
    const counter = form.querySelector('[data-coursefiles-selection-count]');
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
