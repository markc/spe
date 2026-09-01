/* Base JS - Mobile-First App Shell
 * Copyright © 2026 Mark Constable <mc@dcs.spa> (MIT License)
 */
if (typeof Base === 'undefined') {
const Base = {
    // All state in single localStorage key
    key: 'base-state',

    // Get/set persistent state. Tolerant of corrupt JSON and unavailable storage
    // (private mode / sandboxed iframe) so one bad value can't break init/restore.
    state(updates) {
        let s = {};
        try { s = JSON.parse(localStorage.getItem(this.key) || '{}') || {}; } catch (e) { s = {}; }
        if (!updates) return s;
        Object.assign(s, updates);
        try { localStorage.setItem(this.key, JSON.stringify(s)); } catch (e) { /* storage unavailable */ }
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
        ['crimson', 'stone', 'ocean', 'forest', 'sunset', 'mono'].forEach(s => html.classList.remove('scheme-' + s));
        if (scheme && scheme !== 'default') html.classList.add('scheme-' + scheme);
        this.state({ scheme: scheme || 'default' });
        document.querySelectorAll('[data-scheme]').forEach(el =>
            el.classList.toggle('active', el.dataset.scheme === (scheme || 'default'))
        );
    },

    // Content width: 'narrow' (~75% of normal — a tighter reading measure),
    // 'normal' (the --container-lg default, no class), or 'wide' (drops the cap to
    // fill the real estate). A deliberate user choice — surfaces may adopt wide-only
    // layouts. Persisted; the pre-paint <head> script applies the class before first
    // paint (no flash).
    setWidth(mode) {
        const m = (mode === 'narrow' || mode === 'wide') ? mode : 'normal';
        const html = document.documentElement;
        html.classList.toggle('narrow', m === 'narrow');
        html.classList.toggle('wide', m === 'wide');
        this.state({ width: m });
        document.querySelectorAll('[data-width]').forEach(el =>
            el.classList.toggle('active', el.dataset.width === m)
        );
    },

    // Toast notification. ms > 0 auto-dismisses after ms; ms <= 0 is STICKY (stays until the
    // user clicks the close ×) — used for errors, which must persist long enough to read/act on.
    toast(msg, type = 'success', ms = 3000) {
        document.querySelector('.toast')?.remove();
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.setAttribute('role', 'alert');
        const span = document.createElement('span');
        span.className = 'toast-msg';
        span.textContent = msg;
        t.appendChild(span);
        const dismiss = () => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); };
        const x = document.createElement('button');
        x.type = 'button';
        x.className = 'toast-close';
        x.setAttribute('aria-label', 'Dismiss');
        x.textContent = '×';
        x.style.cssText = 'background:none;border:0;color:inherit;font:inherit;font-size:1.2em;line-height:1;cursor:pointer;opacity:.7;padding:0;margin-inline-start:.5rem';
        x.addEventListener('click', dismiss);
        t.appendChild(x);
        t.style.display = 'flex';
        t.style.alignItems = 'center';
        document.body.appendChild(t);
        if (ms > 0) setTimeout(dismiss, ms);
        return t;
    },

    // Sidebar: toggle open/close (each side is autonomous)
    toggleSidebar(side) {
        const sb = document.querySelector(`.sidebar-${side}`);
        if (!sb) return;
        const opening = !sb.classList.contains('open');
        if (opening) {
            sb.classList.add('open');
            this.state({ [side + 'Open']: true });
        } else {
            // Close unpins (otherwise restore would re-open on next load)
            sb.classList.remove('open', 'pinned');
            document.body.classList.remove(side + '-pinned');
            this.state({ [side + 'Open']: false, [side + 'Pinned']: false });
        }
    },

    // Set theme explicitly (light/dark)
    setTheme(theme) {
        const html = document.documentElement;
        html.classList.remove('light', 'dark');
        html.classList.add(theme);
        this.state({ theme });
        this.updateIcon();
        document.querySelectorAll('[data-theme]').forEach(el =>
            el.classList.toggle('active', el.dataset.theme === theme)
        );
    },

    // Carousel transition mode (slide/fade)
    setCarouselMode(mode) {
        this.state({ carousel: mode });
        document.querySelectorAll('.sidebar').forEach(sb => {
            const track = sb.querySelector('.panel-track');
            if (!track) return;
            const panels = track.querySelectorAll('.panel');
            const side = sb.classList.contains('sidebar-left') ? 'left' : 'right';
            const idx = this.state()[side + 'Panel'] || 0;
            // Kill all transitions during mode switch
            track.style.transition = 'none';
            panels.forEach(p => p.style.transition = 'none');
            if (mode === 'fade') {
                panels.forEach((p, i) => p.classList.toggle('active', i === idx));
                track.style.transform = '';
                sb.classList.add('fade-mode');
            } else {
                track.style.transform = `translateX(-${idx * 100}%)`;
                sb.classList.remove('fade-mode');
                panels.forEach(p => p.classList.remove('active'));
            }
            // Force reflow then restore transitions
            track.offsetHeight;
            track.style.transition = '';
            panels.forEach(p => p.style.transition = '');
        });
        document.querySelectorAll('[data-carousel]').forEach(el =>
            el.classList.toggle('active', el.dataset.carousel === mode)
        );
    },

    // Apply a sidebar width to the DOM (no persistence). pct clamped 10..100,
    // rounded to whole percent. Used live while dragging the resize handle.
    applySidebarWidth(side, pct) {
        pct = Math.max(10, Math.min(100, Math.round(pct)));
        document.documentElement.style.setProperty(`--sidebar-width-${side}`, pct + '%');
        const input = document.querySelector(`.sidebar-width-spinner[data-side="${side}"]`);
        if (input && parseInt(input.value) !== pct) input.value = pct;
        return pct;
    },

    // Set + persist sidebar width (side = 'left' | 'right'). The spinner path
    // snaps to step 5; drag passes an already-whole percent for 1% precision.
    setSidebarWidth(side, pct, snap = true) {
        if (snap) pct = Math.round(pct / 5) * 5;
        pct = this.applySidebarWidth(side, pct);
        const key = side === 'left' ? 'sidebarWidthLeft' : 'sidebarWidthRight';
        this.state({ [key]: pct });
        return pct;
    },

    // Panel carousel: navigate to panel index.
    // When the chevron wraps (last→first or first→last), we keep sliding in
    // the chevron's direction by briefly offsetting the destination panel to
    // the far side of the track, sliding the track past the edge, then
    // snapping both back to the canonical position without animation.
    setPanel(side, index) {
        const sb = document.querySelector(`.sidebar-${side}`);
        if (!sb) return;
        const track = sb.querySelector('.panel-track');
        if (!track) return;
        const panels = track.querySelectorAll('.panel');
        const count = panels.length;
        if (count === 0) return;

        // If a previous wrap is mid-cleanup, cancel it and reset the dest panel
        if (track._wrapCleanup) {
            clearTimeout(track._wrapCleanup.timer);
            track._wrapCleanup.panel.style.transform = '';
            track._wrapCleanup = null;
        }

        const normalized = ((index % count) + count) % count;
        const isFade = sb.classList.contains('fade-mode');

        if (isFade) {
            panels.forEach((p, i) => p.classList.toggle('active', i === normalized));
        } else {
            const wrapForward = index >= count;
            const wrapBack = index < 0;
            if (wrapForward || wrapBack) {
                const destPanel = panels[normalized];
                destPanel.style.transform = wrapForward
                    ? `translateX(${count * 100}%)`
                    : `translateX(-${count * 100}%)`;
                track.offsetHeight; // commit layout before animating
                track.style.transform = wrapForward
                    ? `translateX(-${count * 100}%)`
                    : `translateX(100%)`;
                const timer = setTimeout(() => {
                    track.style.transition = 'none';
                    destPanel.style.transform = '';
                    track.style.transform = `translateX(-${normalized * 100}%)`;
                    track.offsetHeight;
                    track.style.transition = '';
                    track._wrapCleanup = null;
                }, 320);
                track._wrapCleanup = { timer, panel: destPanel };
            } else {
                track.style.transform = `translateX(-${normalized * 100}%)`;
            }
        }
        // Update dots
        sb.querySelectorAll('.carousel-dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === normalized);
        });
        this.state({ [side + 'Panel']: normalized });
    },

    // Sidebar: pin/unpin (desktop)
    pinSidebar(side) {
        const sb = document.querySelector(`.sidebar-${side}`);
        if (!sb) return;
        const pinning = !sb.classList.contains('pinned');
        sb.classList.toggle('pinned', pinning);
        sb.classList.toggle('open', pinning);
        document.body.classList.toggle(side + '-pinned', pinning);
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

    // Restore state on page load
    restore() {
        const s = this.state();
        const desktop = window.innerWidth >= 960; // mirrors base.css @media (min-width: 960px)

        // Restore sidebar widths (verbatim — drag stores 1% precision)
        if (s.sidebarWidthLeft) this.applySidebarWidth('left', s.sidebarWidthLeft);
        if (s.sidebarWidthRight) this.applySidebarWidth('right', s.sidebarWidthRight);

        // Restore carousel transition mode
        const mode = s.carousel || 'slide';
        if (mode === 'fade') {
            document.querySelectorAll('.sidebar').forEach(sb => sb.classList.add('fade-mode'));
        }
        document.querySelectorAll('[data-carousel]').forEach(el =>
            el.classList.toggle('active', el.dataset.carousel === mode)
        );

        // Restore theme toggle buttons
        const theme = s.theme || (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        document.querySelectorAll('[data-theme]').forEach(el =>
            el.classList.toggle('active', el.dataset.theme === theme)
        );

        // Restore content-width toggle buttons (the narrow/wide class itself is applied
        // pre-paint by the <head> script; this just marks the active button).
        const width = (s.width === 'narrow' || s.width === 'wide') ? s.width : 'normal';
        document.querySelectorAll('[data-width]').forEach(el =>
            el.classList.toggle('active', el.dataset.width === width)
        );

        ['left', 'right'].forEach(side => {
            const sb = document.querySelector(`.sidebar-${side}`);
            if (!sb) return;
            // First visit (no saved key): pinned on desktop, closed below the pin regime.
            // Saved state always wins. Both values are coerced to real booleans because
            // classList.toggle(name, undefined) TOGGLES instead of forcing false — that
            // accident used to open + pin both sidebars on every fresh visit, phones included.
            const savedPinned = s[side + 'Pinned'];
            const pinned = (savedPinned === undefined ? true : Boolean(savedPinned)) && desktop;
            const open = pinned || (Boolean(s[side + 'Open']) && desktop);
            sb.classList.toggle('pinned', pinned);
            sb.classList.toggle('open', open);
            document.body.classList.toggle(side + '-pinned', pinned);
            // Set correct pin icon
            const icon = sb.querySelector('.pin-toggle [data-lucide], .pin-toggle svg');
            if (icon && pinned) icon.setAttribute('data-lucide', 'pin-off');
            // Restore panel carousel position
            const panelIndex = s[side + 'Panel'] || 0;
            if (panelIndex > 0) this.setPanel(side, panelIndex);
            else if (mode === 'fade') {
                // Ensure first panel is active in fade mode
                const panels = sb.querySelectorAll('.panel');
                if (panels[0]) panels[0].classList.add('active');
            }
        });

        // Restore tree expanded state
        const tree = document.querySelector('.tree');
        if (tree && Array.isArray(s.treeExpanded)) {
            const branches = tree.querySelectorAll('.tree-branch');
            branches.forEach((b, idx) => {
                const shouldExpand = s.treeExpanded.includes(idx);
                b.classList.toggle('collapsed', !shouldExpand);
                // Update folder icon
                const icon = b.querySelector('.tree-toggle [data-lucide="folder"], .tree-toggle [data-lucide="folder-open"]');
                if (icon) icon.setAttribute('data-lucide', shouldExpand ? 'folder-open' : 'folder');
            });
        }
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

            // Theme toggle (legacy icon button)
            if (t.closest('.theme-toggle')) { this.toggleTheme(); return; }

            // Theme buttons (Light/Dark)
            const themeBtn = t.closest('[data-theme]');
            if (themeBtn) { this.setTheme(themeBtn.dataset.theme); return; }

            // Carousel mode buttons (Slide/Fade)
            const carouselBtn = t.closest('[data-carousel]');
            if (carouselBtn) { this.setCarouselMode(carouselBtn.dataset.carousel); return; }

            // Content width buttons (Narrow/Normal/Wide)
            const widthBtn = t.closest('[data-width]');
            if (widthBtn) { this.setWidth(widthBtn.dataset.width); return; }

            // Scheme selector
            const scheme = t.closest('[data-scheme]');
            if (scheme) { e.preventDefault(); this.setScheme(scheme.dataset.scheme); return; }

            // Sidebar toggle
            const menuBtn = t.closest('.menu-toggle[data-sidebar]');
            if (menuBtn) { this.toggleSidebar(menuBtn.dataset.sidebar); return; }

            // Pin toggle
            const pinBtn = t.closest('.pin-toggle[data-sidebar]');
            if (pinBtn) { this.pinSidebar(pinBtn.dataset.sidebar); return; }

            // Carousel chevron
            const chevron = t.closest('.carousel-chevron[data-sidebar]');
            if (chevron) {
                const side = chevron.dataset.sidebar;
                const s = this.state();
                const cur = s[side + 'Panel'] || 0;
                this.setPanel(side, chevron.dataset.dir === 'prev' ? cur - 1 : cur + 1);
                return;
            }

            // Carousel dot
            const dot = t.closest('.carousel-dot[data-sidebar]');
            if (dot) {
                this.setPanel(dot.dataset.sidebar, parseInt(dot.dataset.panel, 10));
                return;
            }

            // Tree toggle (expand/collapse)
            const treeToggle = t.closest('.tree-toggle');
            if (treeToggle) {
                const branch = treeToggle.closest('.tree-branch');
                if (!branch) return;
                const collapsed = branch.classList.toggle('collapsed');
                // Swap folder icon
                const icon = treeToggle.querySelector('[data-lucide="folder"], [data-lucide="folder-open"], svg');
                if (icon && typeof lucide !== 'undefined') {
                    const i = document.createElement('i');
                    i.setAttribute('data-lucide', collapsed ? 'folder' : 'folder-open');
                    icon.replaceWith(i);
                    lucide.createIcons({ nodes: [i] });
                }
                // Persist expanded state
                const tree = branch.closest('.tree');
                if (tree) {
                    const branches = tree.querySelectorAll('.tree-branch');
                    const expanded = [];
                    branches.forEach((b, idx) => { if (!b.classList.contains('collapsed')) expanded.push(idx); });
                    this.state({ treeExpanded: expanded });
                }
                return;
            }

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
        });

        // Escape key closes dropdowns (sidebars are hamburger-only)
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            }
        });

        // System theme change
        matchMedia('(prefers-color-scheme:dark)').addEventListener('change', e => {
            if (!this.state().theme) {
                document.documentElement.classList.replace(e.matches ? 'light' : 'dark', e.matches ? 'dark' : 'light');
                this.updateIcon();
            }
        });

        // Responsive: hide pinned sidebars when viewport shrinks below the pin regime
        matchMedia('(min-width: 960px)').addEventListener('change', e => {
            if (!e.matches) {
                // Viewport went below desktop - close all sidebars
                document.querySelectorAll('.sidebar.open').forEach(sb => {
                    sb.classList.remove('open', 'pinned');
                });
                document.body.classList.remove('left-pinned', 'right-pinned');
            } else {
                // Viewport went to desktop - restore pinned state
                this.restore();
            }
        });

        // Sidebar width spinners (independent left/right, 10-100%, step 5)
        document.querySelectorAll('.sidebar-width-spinner').forEach(input => {
            const apply = e => {
                const v = parseInt(e.target.value);
                if (Number.isFinite(v)) this.setSidebarWidth(e.target.dataset.side, v);
            };
            input.addEventListener('change', apply);
            input.addEventListener('blur', apply);
        });

        // Sidebar resize handles: inject one on each sidebar's inner edge and
        // wire pointer-drag → live width var, persisting the final width on release.
        ['left', 'right'].forEach(side => {
            const sb = document.querySelector(`.sidebar-${side}`);
            if (!sb || sb.querySelector('.sidebar-resizer')) return;
            const handle = document.createElement('div');
            handle.className = 'sidebar-resizer';
            handle.dataset.side = side;
            sb.appendChild(handle);
        });

        let drag = null;
        document.addEventListener('pointerdown', e => {
            const handle = e.target.closest('.sidebar-resizer');
            if (!handle) return;
            e.preventDefault();
            drag = { side: handle.dataset.side, pct: null };
            handle.setPointerCapture(e.pointerId);
            document.body.classList.add('resizing');
        });
        document.addEventListener('pointermove', e => {
            if (!drag) return;
            const vw = window.innerWidth;
            const raw = drag.side === 'left'
                ? (e.clientX / vw) * 100
                : ((vw - e.clientX) / vw) * 100;
            drag.pct = this.applySidebarWidth(drag.side, raw);
        });
        const endDrag = () => {
            if (!drag) return;
            document.body.classList.remove('resizing');
            if (drag.pct !== null) {
                const key = drag.side === 'left' ? 'sidebarWidthLeft' : 'sidebarWidthRight';
                this.state({ [key]: drag.pct });
            }
            drag = null;
        };
        document.addEventListener('pointerup', endDrag);
        document.addEventListener('pointercancel', endDrag);

        // Scroll detection: seamless topnav/sidebar header effect
        const onScroll = () => document.body.classList.toggle('scrolled', window.scrollY > 0);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

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
