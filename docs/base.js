/* Base JavaScript Framework
 * Generic utilities for theme, navigation, toasts, and animations
 * Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)
 */

// Prevent redeclaration when htmx re-processes the page
if (typeof Base !== 'undefined') {
    // Already loaded, skip
} else {

const Base = {
    // Storage keys
    storageKey: 'base-theme',
    schemeKey: 'base-scheme',
    leftPinnedKey: 'left-pinned',
    rightPinnedKey: 'right-pinned',

    // Initialize theme (called inline in head to prevent FOUC)
    initTheme() {
        const html = document.documentElement;
        const storedTheme = localStorage.getItem(this.storageKey);
        const storedScheme = localStorage.getItem(this.schemeKey);

        // Set theme
        html.classList.remove('light', 'dark');
        if (storedTheme) {
            html.classList.add(storedTheme);
        } else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.classList.add(prefersDark ? 'dark' : 'light');
        }

        // Set scheme
        if (storedScheme && storedScheme !== 'default') {
            html.classList.add('scheme-' + storedScheme);
        }

        this.updateThemeIcon();
    },

    // Toggle between light and dark themes
    toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        html.classList.remove('light', 'dark');
        html.classList.add(isDark ? 'light' : 'dark');
        localStorage.setItem(this.storageKey, isDark ? 'light' : 'dark');
        this.updateThemeIcon();
    },

    // Update theme toggle button icon
    updateThemeIcon() {
        const btn = document.getElementById('theme-icon');
        if (btn) {
            const isDark = document.documentElement.classList.contains('dark');
            btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            // Support both emoji (chapters 01-04) and Lucide icons (chapters 05+)
            const icon = btn.querySelector('i[data-lucide], svg');
            if (icon) {
                const newIcon = isDark ? 'sun' : 'moon';
                if (icon.tagName === 'svg') {
                    // Replace existing SVG with new icon
                    const newI = document.createElement('i');
                    newI.setAttribute('data-lucide', newIcon);
                    icon.replaceWith(newI);
                    if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [newI] });
                } else {
                    icon.setAttribute('data-lucide', newIcon);
                    if (typeof lucide !== 'undefined') lucide.createIcons({ nodes: [icon] });
                }
            } else {
                // Fallback to emoji for chapters without Lucide
                btn.textContent = isDark ? '☀️' : '🌙';
            }
        }
    },

    // Set color scheme
    setScheme(scheme) {
        const html = document.documentElement;
        // Remove existing scheme classes
        html.classList.remove('scheme-ocean', 'scheme-forest', 'scheme-sunset');
        // Add new scheme if not default
        if (scheme && scheme !== 'default') {
            html.classList.add('scheme-' + scheme);
        }
        localStorage.setItem(this.schemeKey, scheme || 'default');
        this.updateSchemeLinks();
    },

    // Update active state on scheme links
    updateSchemeLinks() {
        const currentScheme = localStorage.getItem(this.schemeKey) || 'default';
        document.querySelectorAll('[data-scheme]').forEach(el => {
            el.classList.toggle('active', el.dataset.scheme === currentScheme);
        });
    },

    // Show toast notification
    showToast(message, type = 'success', duration = 3000) {
        // Remove existing toast
        const existing = document.querySelector('.toast');
        if (existing) {
            existing.remove();
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');
        document.body.appendChild(toast);

        // Auto-remove after duration
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%) scale(0.9)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    // Toggle sidebar open/close
    toggleSidebar(side) {
        const sidebar = document.querySelector(`.sidebar-${side}`);
        if (!sidebar) return;

        const isOpen = sidebar.classList.contains('open');
        // Close any open sidebar first
        document.querySelectorAll('.sidebar.open').forEach(s => s.classList.remove('open'));
        document.body.classList.remove('sidebar-open');

        if (!isOpen) {
            sidebar.classList.add('open');
            document.body.classList.add('sidebar-open');
        }
    },

    // Close all sidebars
    closeSidebars() {
        document.querySelectorAll('.sidebar.open').forEach(s => s.classList.remove('open'));
        document.body.classList.remove('sidebar-open');
    },

    // Pin/unpin sidebar (desktop only)
    pinSidebar(side) {
        const sidebar = document.querySelector(`.sidebar-${side}`);
        if (!sidebar) return;

        const isPinned = sidebar.classList.toggle('pinned');
        document.body.classList.toggle(`${side}-pinned`, isPinned);
        localStorage.setItem(side === 'left' ? this.leftPinnedKey : this.rightPinnedKey, isPinned);

        // If pinning, keep sidebar open; if unpinning, close it
        if (isPinned) {
            sidebar.classList.add('open');
        } else {
            sidebar.classList.remove('open');
            document.body.classList.remove('sidebar-open');
        }

        // Update pin icon
        const pinBtn = sidebar.querySelector('.pin-toggle i, .pin-toggle svg');
        if (pinBtn && typeof lucide !== 'undefined') {
            pinBtn.setAttribute('data-lucide', isPinned ? 'pin-off' : 'pin');
            lucide.createIcons({ nodes: [pinBtn] });
        }
    },

    // Initialize pinned state from localStorage
    initSidebars() {
        ['left', 'right'].forEach(side => {
            const key = side === 'left' ? this.leftPinnedKey : this.rightPinnedKey;
            const isPinned = localStorage.getItem(key) === 'true';
            if (isPinned && window.innerWidth >= 1280) {
                const sidebar = document.querySelector(`.sidebar-${side}`);
                if (sidebar) {
                    sidebar.classList.add('pinned', 'open');
                    document.body.classList.add(`${side}-pinned`);
                    const pinBtn = sidebar.querySelector('.pin-toggle i');
                    if (pinBtn) pinBtn.setAttribute('data-lucide', 'pin-off');
                }
            }
        });
    },

    // Toggle dropdown menu
    toggleDropdown(el) {
        const dropdown = el.closest('.dropdown');
        if (dropdown) {
            // Close other dropdowns first
            document.querySelectorAll('.dropdown.open').forEach(d => {
                if (d !== dropdown) d.classList.remove('open');
            });
            dropdown.classList.toggle('open');
        }
    },

    // Nav scroll effect
    initNavScroll() {
        const nav = document.querySelector('nav.topnav, nav.scrolled-nav');
        if (!nav) return;

        const handleScroll = () => {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        };

        // Use passive listener for better scroll performance
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll(); // Initial check
    },

    // Scroll reveal animation
    initScrollReveal() {
        const reveals = document.querySelectorAll('.reveal');
        if (!reveals.length) return;

        // Check if reduced motion is preferred
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            reveals.forEach(el => el.classList.add('visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        reveals.forEach(el => observer.observe(el));
    },

    // Staggered animation for children
    initStaggerAnimation() {
        const staggerContainers = document.querySelectorAll('.stagger');
        if (!staggerContainers.length) return;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            staggerContainers.forEach(container => {
                container.querySelectorAll(':scope > *').forEach(child => {
                    child.style.opacity = '1';
                    child.style.animation = 'none';
                });
            });
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        staggerContainers.forEach(container => observer.observe(container));
    },

    // AJAX page loading (optional)
    async loadPage(url, target = '#main') {
        const container = document.querySelector(target);
        if (!container) return;

        try {
            container.style.opacity = '0.5';
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            container.innerHTML = html;
            container.style.opacity = '1';
            history.pushState({}, '', url);

            // Re-run inline scripts
            container.querySelectorAll('script').forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                script.parentNode.replaceChild(newScript, script);
            });

            // Re-init animations
            this.initScrollReveal();
        } catch (error) {
            container.innerHTML = `<div class="card"><p class="text-danger">Error: ${error.message}</p></div>`;
            container.style.opacity = '1';
        }
    },

    // Smooth scroll to anchor
    scrollToAnchor(hash) {
        const target = document.querySelector(hash);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    },

    // Initialize all event listeners
    init() {
        // Theme is already initialized inline, just update icon
        this.updateThemeIcon();

        // Initialize pinned sidebar states
        this.initSidebars();

        // Update active state on scheme links
        this.updateSchemeLinks();

        // Theme toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.addEventListener('click', () => this.toggleTheme());
        });

        // Color scheme selectors
        document.querySelectorAll('[data-scheme]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.setScheme(el.dataset.scheme);
            });
        });

        // Sidebar toggle (hamburger menus)
        document.querySelectorAll('.menu-toggle[data-sidebar]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleSidebar(btn.dataset.sidebar);
            });
        });

        // Sidebar pin toggle
        document.querySelectorAll('.pin-toggle[data-sidebar]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.pinSidebar(btn.dataset.sidebar);
            });
        });

        // Close sidebar when clicking overlay
        const overlay = document.querySelector('.overlay');
        if (overlay) {
            overlay.addEventListener('click', () => this.closeSidebars());
        }

        // Dropdown toggle (click for mobile)
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown(toggle);
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.open').forEach(d => {
                    d.classList.remove('open');
                });
            }
        });

        // AJAX links (add class="ajax-link" to enable)
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.ajax-link');
            if (link && link.href) {
                e.preventDefault();
                this.loadPage(link.href);
            }
        });

        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            this.loadPage(window.location.href);
        });

        // Close non-pinned sidebars when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                document.querySelectorAll('.sidebar.open:not(.pinned)').forEach(s => {
                    s.classList.remove('open');
                });
                if (!document.querySelector('.sidebar.pinned.open')) {
                    document.body.classList.remove('sidebar-open');
                }
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const hash = anchor.getAttribute('href');
                if (hash && hash !== '#') {
                    e.preventDefault();
                    this.scrollToAnchor(hash);
                    history.pushState(null, '', hash);
                }
            });
        });

        // Escape key to close menus and non-pinned sidebars
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
                document.querySelectorAll('.sidebar.open:not(.pinned)').forEach(s => s.classList.remove('open'));
                if (!document.querySelector('.sidebar.pinned.open')) {
                    document.body.classList.remove('sidebar-open');
                }
            }
        });

        // Initialize nav scroll effect
        this.initNavScroll();

        // Initialize scroll reveal
        this.initScrollReveal();

        // Initialize stagger animations
        this.initStaggerAnimation();

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(this.storageKey)) {
                const html = document.documentElement;
                html.classList.remove('light', 'dark');
                html.classList.add(e.matches ? 'dark' : 'light');
                this.updateThemeIcon();
            }
        });

        // Initialize Lucide icons (if available)
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Base.init());
} else {
    Base.init();
}

// Expose globally for inline handlers
window.Base = Base;
window.showToast = (msg, type) => Base.showToast(msg, type);
window.toggleTheme = () => Base.toggleTheme();

// Backwards compatibility aliases
window.SPE = Base;

} // End of redeclaration guard
