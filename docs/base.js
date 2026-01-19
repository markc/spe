/* Base JavaScript Framework
 * Generic utilities for theme, navigation, toasts, and animations
 * Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)
 */

const Base = {
    // Storage keys
    storageKey: 'base-theme',
    sidebarKey: 'base-sidebar',

    // Initialize theme (called inline in head to prevent FOUC)
    initTheme() {
        const stored = localStorage.getItem(this.storageKey);
        if (stored) {
            document.documentElement.className = stored;
        } else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.className = prefersDark ? 'dark' : 'light';
        }
        this.updateThemeIcon();
    },

    // Toggle between light and dark themes
    toggleTheme() {
        const current = document.documentElement.className;
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.className = next;
        localStorage.setItem(this.storageKey, next);
        this.updateThemeIcon();
    },

    // Update theme toggle button icon
    updateThemeIcon() {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            const isDark = document.documentElement.className === 'dark';
            icon.textContent = isDark ? '☀️' : '🌙';
            icon.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }
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

    // Toggle mobile sidebar
    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('open');
            // Toggle body scroll lock
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }
    },

    // Initialize sidebar collapsed state from localStorage
    initSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        const stored = localStorage.getItem(this.sidebarKey);
        if (stored === 'collapsed') {
            sidebar.classList.add('collapsed');
        }
    },

    // Initialize tooltips for collapsed sidebar links
    initSidebarTooltips() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        const tooltip = document.createElement('div');
        tooltip.className = 'sidebar-tooltip';
        document.body.appendChild(tooltip);

        sidebar.querySelectorAll('nav a[title]').forEach(link => {
            const title = link.getAttribute('title');
            link.addEventListener('mouseenter', () => {
                if (!sidebar.classList.contains('collapsed')) return;
                link.removeAttribute('title'); // Disable native tooltip
                tooltip.textContent = title;
                const rect = link.getBoundingClientRect();
                tooltip.style.top = `${rect.top + rect.height / 2}px`;
                tooltip.style.left = `${rect.right + 4}px`;
                tooltip.classList.add('visible');
            });
            link.addEventListener('mouseleave', () => {
                link.setAttribute('title', title); // Restore for non-collapsed
                tooltip.classList.remove('visible');
            });
        });
    },

    // Toggle sidebar collapsed state (desktop)
    collapseSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem(this.sidebarKey, isCollapsed ? 'collapsed' : 'expanded');
    },

    // Toggle mobile nav menu
    toggleMenu() {
        const menu = document.querySelector('.topnav-links');
        if (menu) {
            menu.classList.toggle('open');
        }
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

        // Initialize sidebar collapsed state
        this.initSidebar();

        // Initialize sidebar tooltips
        this.initSidebarTooltips();

        // Theme toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.addEventListener('click', () => this.toggleTheme());
        });

        // Sidebar collapse toggle (desktop)
        document.querySelectorAll('.sidebar-toggle').forEach(btn => {
            btn.addEventListener('click', () => this.collapseSidebar());
        });

        // Collapsible sidebar groups
        document.querySelectorAll('.sidebar-group-title').forEach(title => {
            title.addEventListener('click', () => {
                title.parentElement.classList.toggle('collapsed');
            });
        });

        // Mobile menu toggle
        document.querySelectorAll('.menu-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleSidebar();
                this.toggleMenu();
            });
        });

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

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                const sidebar = document.querySelector('.sidebar.open');
                if (sidebar) {
                    sidebar.classList.remove('open');
                    document.body.style.overflow = '';
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

        // Escape key to close menus
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.dropdown.open').forEach(d => {
                    d.classList.remove('open');
                });
                const sidebar = document.querySelector('.sidebar.open');
                if (sidebar) {
                    sidebar.classList.remove('open');
                    document.body.style.overflow = '';
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
                document.documentElement.className = e.matches ? 'dark' : 'light';
                this.updateThemeIcon();
            }
        });
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
