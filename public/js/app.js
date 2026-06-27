/* GED Modern - JavaScript Utilities v2.0 */

// Theme Management
class ThemeManager {
    constructor() {
        this.THEME_KEY = 'ged-theme-preference';
        this.init();
    }

    init() {
        const saved = localStorage.getItem(this.THEME_KEY) || 'light';
        this.setTheme(saved);
        this.setupToggle();
    }

    setTheme(theme) {
        const isDark = theme === 'dark';
        document.body.classList.toggle('dark-theme', isDark);
        localStorage.setItem(this.THEME_KEY, theme);
        
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            toggle.innerHTML = isDark ? '☀️' : '🌙';
        }
    }

    setupToggle() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.theme-toggle')) {
                const current = localStorage.getItem(this.THEME_KEY) || 'light';
                const newTheme = current === 'light' ? 'dark' : 'light';
                this.setTheme(newTheme);
            }
        });
    }

    toggle() {
        const current = localStorage.getItem(this.THEME_KEY) || 'light';
        this.setTheme(current === 'light' ? 'dark' : 'light');
    }
}

// User Menu Dropdown
class UserMenuDropdown {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('click', (e) => {
            const profile = e.target.closest('.user-profile');
            if (profile) {
                this.toggle();
            } else if (!e.target.closest('.dropdown-menu')) {
                this.close();
            }
        });
    }

    toggle() {
        const menu = document.querySelector('.dropdown-menu');
        if (menu) {
            menu.classList.toggle('active');
        }
    }

    close() {
        const menu = document.querySelector('.dropdown-menu');
        if (menu) {
            menu.classList.remove('active');
        }
    }
}

// Modal Management
class Modal {
    constructor(id) {
        this.id = id;
        this.modal = document.getElementById(id);
        if (!this.modal) return;

        this.setupEventListeners();
    }

    setupEventListeners() {
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        const closeBtn = this.modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.close();
            }
        });
    }

    open() {
        if (this.modal) {
            this.modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    close() {
        if (this.modal) {
            this.modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }

    toggle() {
        if (this.modal) {
            this.modal.classList.toggle('active');
            document.body.style.overflow = this.modal.classList.contains('active') ? 'hidden' : 'auto';
        }
    }
}

// Alert/Notification System
class NotificationSystem {
    static show(message, type = 'info', duration = 4000) {
        const container = document.querySelector('.notification-container') || this.createContainer();
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} notification-item`;
        alert.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span>${this.getIcon(type)}</span>
                <span>${message}</span>
            </div>
        `;
        
        container.appendChild(alert);
        
        if (duration) {
            setTimeout(() => {
                alert.remove();
            }, duration);
        }
        
        return alert;
    }

    static createContainer() {
        const container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 80px;
            right: 2rem;
            z-index: 3000;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        `;
        document.body.appendChild(container);
        return container;
    }

    static getIcon(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }

    static success(message, duration) {
        return this.show(message, 'success', duration);
    }

    static error(message, duration) {
        return this.show(message, 'error', duration);
    }

    static warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    static info(message, duration) {
        return this.show(message, 'info', duration);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
    new UserMenuDropdown();
});

// Helper functions
window.showNotification = (message, type = 'info', duration = 4000) => {
    NotificationSystem.show(message, type, duration);
};

window.openModal = (id) => {
    const modal = new Modal(id);
    modal.open();
};

window.closeModal = (id) => {
    const modal = new Modal(id);
    modal.close();
};

// Sidebar toggle para mobile
class SidebarToggle {
    constructor() {
        this.init();
    }

    init() {
        const toggle = document.querySelector('.sidebar-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                }
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SidebarToggle();
});

