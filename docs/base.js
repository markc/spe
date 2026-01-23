/* Base JS - Mobile-First App Shell
 * Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
 */
if (typeof Base === 'undefined') {
const Base = {
    // All state in single localStorage key
    key: 'base-state',

    // Get/set persistent state
    state(updates) {
        const s = JSON.parse(localStorage.getItem(this.key) || '{}');
        if (!updates) return s;
        Object.assign(s, updates);
        localStorage.setItem(this.key, JSON.stringify(s));
        return s;
    },

    // Theme: toggle dark/light
    toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        html.classList.replace(isDark ? 'dark' : 'light', isDark ? 'light' : 'dark');
        this.state({ theme: isDark ? 'light' : 'dark' });
        this.updateIcon();
    },

    // Update theme icon (sun/moon)
    updateIcon() {
        const btn = document.getElementById('theme-icon');
        if (!btn) return;
        const isDark = document.documentElement.classList.contains('dark');
        btn.setAttribute('aria-label', isDark ? 'Light mode' : 'Dark mode');
        const icon = btn.querySelector('[data-lucide], svg');
        if (icon && typeof lucide !== 'undefined') {
            const i = document.createElement('i');
            i.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
            icon.replaceWith(i);
            lucide.createIcons({ nodes: [i] });
        } else if (!icon) {
            btn.textContent = isDark ? '☀️' : '🌙';
        }
    },

    // Color scheme
    setScheme(scheme) {
        const html = document.documentElement;
        ['ocean', 'forest', 'sunset'].forEach(s => html.classList.remove('scheme-' + s));
        if (scheme && scheme !== 'default') html.classList.add('scheme-' + scheme);
        this.state({ scheme: scheme || 'default' });
        document.querySelectorAll('[data-scheme]').forEach(el =>
            el.classList.toggle('active', el.dataset.scheme === (scheme || 'default'))
        );
    },

    // Toast notification
    toast(msg, type = 'success', ms = 3000) {
        document.querySelector('.toast')?.remove();
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.textContent = msg;
        t.setAttribute('role', 'alert');
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, ms);
    },

    // Sidebar: toggle open/close
    toggleSidebar(side) {
        const sb = document.querySelector(`.sidebar-${side}`);
        if (!sb) return;
        const opening = !sb.classList.contains('open');
        // Close all non-pinned sidebars first
        document.querySelectorAll('.sidebar.open:not(.pinned)').forEach(s => s.classList.remove('open'));
        if (opening) {
            sb.classList.add('open');
            document.body.classList.add('sidebar-open');
            this.state({ [side + 'Open']: true });
        } else {
            // Also unpin if pinned
            sb.classList.remove('open', 'pinned');
            document.body.classList.remove(side + '-pinned');
            if (!document.querySelector('.sidebar.open')) document.body.classList.remove('sidebar-open');
            this.state({ [side + 'Open']: false, [side + 'Pinned']: false });
        }
    },

    // Sidebar: pin/unpin (desktop)
    pinSidebar(side) {
        const sb = document.querySelector(`.sidebar-${side}`);
        if (!sb) return;
        const pinning = !sb.classList.contains('pinned');
        sb.classList.toggle('pinned', pinning);
        sb.classList.toggle('open', pinning);
        document.body.classList.toggle(side + '-pinned', pinning);
        if (!pinning && !document.querySelector('.sidebar.open')) document.body.classList.remove('sidebar-open');
        this.state({ [side + 'Pinned']: pinning, [side + 'Open']: pinning });
        // Update pin icon
        const icon = sb.querySelector('.pin-toggle [data-lucide], .pin-toggle svg');
        if (icon && typeof lucide !== 'undefined') {
            const i = document.createElement('i');
            i.setAttribute('data-lucide', pinning ? 'pin-off' : 'pin');
            icon.replaceWith(i);
            lucide.createIcons({ nodes: [i] });
        }
    },

    // Close all non-pinned sidebars
    closeSidebars() {
        document.querySelectorAll('.sidebar.open:not(.pinned)').forEach(s => s.classList.remove('open'));
        if (!document.querySelector('.sidebar.pinned.open')) document.body.classList.remove('sidebar-open');
        this.state({ leftOpen: false, rightOpen: false });
    },

    // Restore state on page load
    restore() {
        const s = this.state();
        const desktop = window.innerWidth >= 1280;

        ['left', 'right'].forEach(side => {
            const sb = document.querySelector(`.sidebar-${side}`);
            if (!sb) return;
            const pinned = s[side + 'Pinned'] && desktop;
            const open = pinned || (s[side + 'Open'] && desktop);
            sb.classList.toggle('pinned', pinned);
            sb.classList.toggle('open', open);
            document.body.classList.toggle(side + '-pinned', pinned);
            if (open) document.body.classList.add('sidebar-open');
            // Set correct pin icon
            const icon = sb.querySelector('.pin-toggle [data-lucide], .pin-toggle svg');
            if (icon && pinned) icon.setAttribute('data-lucide', 'pin-off');
        });
    },

    // Initialize
    init() {
        this.updateIcon();
        this.restore();

        // Scheme links
        const s = this.state();
        document.querySelectorAll('[data-scheme]').forEach(el =>
            el.classList.toggle('active', el.dataset.scheme === (s.scheme || 'default'))
        );

        // Event delegation for clicks
        document.addEventListener('click', e => {
            const t = e.target;

            // Theme toggle
            if (t.closest('.theme-toggle')) { this.toggleTheme(); return; }

            // Scheme selector
            const scheme = t.closest('[data-scheme]');
            if (scheme) { e.preventDefault(); this.setScheme(scheme.dataset.scheme); return; }

            // Sidebar toggle
            const menuBtn = t.closest('.menu-toggle[data-sidebar]');
            if (menuBtn) { this.toggleSidebar(menuBtn.dataset.sidebar); return; }

            // Pin toggle
            const pinBtn = t.closest('.pin-toggle[data-sidebar]');
            if (pinBtn) { this.pinSidebar(pinBtn.dataset.sidebar); return; }

            // Overlay click
            if (t.closest('.overlay')) { this.closeSidebars(); return; }

            // Sidebar group toggle (collapsible)
            const groupTitle = t.closest('.sidebar-group-title');
            if (groupTitle) {
                const group = groupTitle.closest('.sidebar-group');
                group?.classList.toggle('collapsed');
                return;
            }

            // Dropdown toggle
            const dropToggle = t.closest('.dropdown-toggle');
            if (dropToggle) {
                e.preventDefault();
                e.stopPropagation();
                const dd = dropToggle.closest('.dropdown');
                document.querySelectorAll('.dropdown.open').forEach(d => d !== dd && d.classList.remove('open'));
                dd?.classList.toggle('open');
                return;
            }

            // Close dropdowns on outside click
            if (!t.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            }

            // Close non-pinned sidebars on outside click
            if (!t.closest('.sidebar') && !t.closest('.menu-toggle')) {
                document.querySelectorAll('.sidebar.open:not(.pinned)').forEach(sb => sb.classList.remove('open'));
                if (!document.querySelector('.sidebar.pinned.open')) document.body.classList.remove('sidebar-open');
            }
        });

        // Escape key closes menus
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
                this.closeSidebars();
            }
        });

        // System theme change
        matchMedia('(prefers-color-scheme:dark)').addEventListener('change', e => {
            if (!this.state().theme) {
                document.documentElement.classList.replace(e.matches ? 'light' : 'dark', e.matches ? 'dark' : 'light');
                this.updateIcon();
            }
        });

        // Responsive: hide pinned sidebars when viewport shrinks to mobile
        matchMedia('(min-width: 1280px)').addEventListener('change', e => {
            if (!e.matches) {
                // Viewport went below desktop - close all sidebars
                document.querySelectorAll('.sidebar.open').forEach(sb => {
                    sb.classList.remove('open', 'pinned');
                });
                document.body.classList.remove('left-pinned', 'right-pinned', 'sidebar-open');
            } else {
                // Viewport went to desktop - restore pinned state
                this.restore();
            }
        });

        // Lucide icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Remove preload class to enable transitions (after state restored)
        requestAnimationFrame(() => document.documentElement.classList.remove('preload'));
    }
};

// Auto-init
document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', () => Base.init())
    : Base.init();

// Global exports
window.Base = Base;
window.showToast = (m, t) => Base.toast(m, t);
window.toggleTheme = () => Base.toggleTheme();
}
