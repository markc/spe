/* SPE - Simple PHP Examples JavaScript
 * Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)
 * Minimal JS for theme toggle, toasts, and optional AJAX navigation
 */

// Theme Management
const SPE = {
    // Initialize theme from localStorage or system preference
    initTheme() {
        const stored = localStorage.getItem('spe-theme');
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
        localStorage.setItem('spe-theme', next);
        this.updateThemeIcon();
    },

    // Update theme toggle button icon
    updateThemeIcon() {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            const isDark = document.documentElement.className === 'dark';
            icon.textContent = isDark ? '☀️' : '🌙';
        }
    },

    // Show toast notification
    showToast(message, type = 'success', duration = 3000) {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    // Toggle mobile sidebar
    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.toggle('open');
    },

    // Toggle mobile nav menu
    toggleMenu() {
        const menu = document.querySelector('.topnav-links');
        if (menu) menu.classList.toggle('open');
    },

    // AJAX page loading (optional, for SPA-like behavior)
    async loadPage(url, target = '#main') {
        const container = document.querySelector(target);
        if (!container) return;

        try {
            container.innerHTML = '<p>Loading...</p>';
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const html = await response.text();
            container.innerHTML = html;
            history.pushState({}, '', url);

            // Re-run any inline scripts
            container.querySelectorAll('script').forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                script.parentNode.replaceChild(newScript, script);
            });
        } catch (error) {
            container.innerHTML = `<p class="toast-danger p-2">Error: ${error.message}</p>`;
        }
    },

    // Toggle dropdown menu (for mobile click)
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

    // Initialize event listeners
    init() {
        this.initTheme();

        // Theme toggle buttons
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.addEventListener('click', () => this.toggleTheme());
        });

        // Collapsible sidebar groups
        document.querySelectorAll('.sidebar-group-title').forEach(title => {
            title.addEventListener('click', () => title.parentElement.classList.toggle('collapsed'));
        });

        // Mobile menu toggle
        document.querySelectorAll('.menu-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleSidebar();
                this.toggleMenu();
            });
        });

        // Dropdown toggle (click for mobile, hover works via CSS)
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
                document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            }
        });

        // AJAX links (optional - add class="ajax-link" to enable)
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
                if (sidebar) sidebar.classList.remove('open');
            }
        });
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => SPE.init());
} else {
    SPE.init();
}

// Expose globally for inline handlers
window.SPE = SPE;
window.showToast = (msg, type) => SPE.showToast(msg, type);
window.toggleTheme = () => SPE.toggleTheme();
