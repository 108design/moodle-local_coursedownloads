// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later
import {publish, subscribe} from 'core/pubsub';
import DrawerEvents from 'core/drawer_events';
import Drawers from 'theme_boost/drawers';
import jQuery from 'jquery';

/** Nonmodal contextual help; use native exclusive drawer coordination when available. */
export const init = config => {
    const panel = document.getElementById('local-coursefiles-context-help');
    if (!panel || panel.dataset.initialised) return;
    panel.dataset.initialised = '1';
    document.body.append(panel);
    const heading = panel.querySelector('h2');
    const content = panel.querySelector('.local-coursefiles-help-content');
    const choice = panel.querySelector('select');
    const helpUrl = new URL(panel.dataset.coursefilesHelpUrl, location.href);
    const hasTopic = topic => [...choice.options].some(option => option.value === topic);
    let opener = null;
    let informationOpener = null;
    document.addEventListener('focusin', event => {
        const icon = event.target.closest('.local-coursefiles-context-help-icon');
        if (icon) informationOpener = icon;
    });
    let activeTopic = '';
    let ownsSpace = false;
    const exclusiveDrawers = Boolean(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED && DrawerEvents.DRAWER_EXCLUSIVE_RELEASED);
    document.body.classList.toggle('local-coursefiles-help-legacy-drawers', !exclusiveDrawers);

    const position = () => {
        const nav = document.querySelector('.navbar.fixed-top');
        const top = nav ? Math.max(0, nav.getBoundingClientRect().bottom) + 12 : 12;
        panel.style.setProperty('--local-coursefiles-help-top', `${top}px`);
    };
    const hide = (returnFocus = true, releaseSpace = true) => {
        if (panel.hidden) return;
        panel.hidden = true;
        document.body.classList.remove('local-coursefiles-help-open');
        // Bootstrap 4 MoreMenu only measures its available width on window resize.
        window.dispatchEvent(new Event('resize'));
        document.querySelectorAll('[data-coursefiles-help-open]').forEach(button => button.setAttribute('aria-expanded', 'false'));
        if (ownsSpace) {
            ownsSpace = false;
            if (releaseSpace) publish(DrawerEvents.DRAWER_EXCLUSIVE_RELEASED, {region: 'right'});
        }
        if (returnFocus && opener?.isConnected) opener.focus();
    };
    const showTopic = topic => {
        const article = [...panel.querySelectorAll('[data-coursefiles-help-article]')]
            .find(node => node.dataset.coursefilesHelpArticle === topic);
        if (!article) return;
        panel.querySelectorAll('article').forEach(node => { node.hidden = node !== article; });
        choice.value = topic;
        if (activeTopic !== topic) content.scrollTop = 0;
        activeTopic = topic;
    };
    const show = (topic, trigger) => {
        if (topic && !hasTopic(topic)) return;
        if (trigger) opener = trigger;
        showTopic(topic || activeTopic || choice.value);
        position();
        if (panel.hidden) {
            if (exclusiveDrawers) {
                publish(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED, {region: 'right'});
                ownsSpace = true;
            }
            panel.hidden = false;
            document.body.classList.add('local-coursefiles-help-open');
            window.dispatchEvent(new Event('resize'));
        }
        document.querySelectorAll('[data-coursefiles-help-open]').forEach(button => button.setAttribute('aria-expanded', 'true'));
        (panel.querySelector('article:not([hidden]) h3') || heading).focus();
    };
    document.addEventListener('click', event => {
        // Native information controls inside labels/disclosures must not change their host control.
        if (event.target.closest('.local-coursefiles-context-help-icon')?.closest('label, summary')) {
            event.preventDefault();
        }
        const close = event.target.closest('[data-coursefiles-help-close]');
        if (close) { hide(); return; }
        const button = event.target.closest('[data-coursefiles-help-open]');
        const link = event.target.closest('a[href]');
        let topic = '';
        if (link) {
            const url = new URL(link.href, window.location.href);
            if (url.origin === location.origin && url.pathname === helpUrl.pathname) {
                const requested = url.searchParams.get('coursefileshelp') || '';
                if (hasTopic(requested)) topic = requested;
            }
        }
        if (!button && !topic) return;
        event.preventDefault();
        if (button && !panel.hidden) { hide(); return; }
        const popover = link?.closest('.popover');
        // Moodle 5.3 describes the popover body, rather than its outer wrapper.
        const describedId = popover?.querySelector('.popover-body')?.id || popover?.id;
        const icon = describedId
            ? document.querySelector(`.help-icon[aria-describedby="${CSS.escape(describedId)}"]`) : null;
        const trigger = button || icon || (informationOpener?.isConnected ? informationOpener : null)
            || document.querySelector('[data-coursefiles-help-open]');
        if (topic) {
            jQuery('.local-coursefiles-context-help-icon').popover('hide');
        }
        show(topic, trigger);
    });
    choice.addEventListener('change', () => showTopic(choice.value));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || panel.hidden || document.querySelector('.modal.show, dialog[open]')) return;
        event.preventDefault();
        hide();
    });
    // Native tools win when explicitly reopened. Their DOM and input state are never replaced.
    document.addEventListener(Drawers.eventTypes.drawerShow, event => {
        if (event.target.classList.contains('drawer-right') && !panel.hidden) hide(false);
    });
    // A second overlay now owns the native exclusive request; do not release its claim.
    if (exclusiveDrawers) subscribe(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED, ({region} = {}) => {
        if (region === 'right' && ownsSpace) hide(false, false);
    });
    document.addEventListener('show.bs.modal', () => hide(false));
    jQuery(document).on('show.bs.modal', () => hide(false));
    window.addEventListener('resize', position);
    if (config.topic) show(config.topic);
};
