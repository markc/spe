// HCP site.js - Progressive enhancements for Hosting Control Panel
// Never modify base.js - all enhancements go here

(function() {
    'use strict';

    // Desktop auto-pin: Pin sidebars on desktop-width screens on initial load
    function initDesktopMode() {
        const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
        const state = JSON.parse(localStorage.getItem('base-state') || '{}');

        // Only auto-pin on first visit (no stored state) and desktop
        if (isDesktop && !state.leftPinned && !state.rightPinned && !state.hasVisited) {
            // Pin left sidebar by default on desktop
            const leftSidebar = document.querySelector('.sidebar-left');
            if (leftSidebar) {
                leftSidebar.classList.add('pinned');
                document.body.classList.add('sidebar-left-pinned');
            }

            // Store that we've visited (so we respect user's unpinning later)
            state.hasVisited = true;
            state.leftPinned = true;
            localStorage.setItem('base-state', JSON.stringify(state));
        }
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDesktopMode);
    } else {
        initDesktopMode();
    }
})();
