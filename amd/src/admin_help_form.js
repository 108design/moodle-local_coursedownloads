// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later

/** Align help in our registered forms without changing fields or their values. */
export const init = () => {
    const navigation = document.querySelector('#usernavigation');
    const alignNavbarHelp = () => {
        if (!navigation) return;
        const help = navigation.querySelector('.local-coursedownloads-help-navbar');
        const children = [...navigation.children];
        const anchor = children.find(node => node.classList.contains('divider')) ||
            children.find(node => node.matches('[data-region="usermenu"], .usermenu-container'));
        if (help && anchor && (help.parentElement !== navigation || help.nextElementSibling !== anchor)) {
            navigation.insertBefore(help, anchor);
        }
    };
    // Navbar callbacks can finish in any order; keep help immediately before the user-area divider.
    alignNavbarHelp();
    if (navigation) new MutationObserver(alignNavbarHelp).observe(navigation, {childList: true});

};
