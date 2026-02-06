(function () {
    const root = document.documentElement;
    const THEME_KEY = 's24_theme';
    const defaultTheme = 'theme-purple';

    const themeColors = {
        'theme-purple': '#6d28d9',
        'theme-emerald': '#10b981',
        'theme-rose': '#e11d48',
        'theme-amber': '#f59e0b',
        'theme-slate': '#334155',
        'theme-cyan': '#06b6d4',
        'theme-pink': '#ec4899'
    };

    const themes = Object.keys(themeColors);

    function applyTheme(theme) {
        // Remove all previous themes from root and body
        root.classList.remove(...themes);
        document.body && document.body.classList.remove(...themes);

        // Add the new theme
        root.classList.add(theme);
        if (document.body) {
            document.body.classList.add(theme);
        }

        // Persist to localStorage
        localStorage.setItem(THEME_KEY, theme);

        // Update all theme swatches markers if any
        document.querySelectorAll('[data-theme]').forEach(swatch => {
            if (swatch.getAttribute('data-theme') === theme) {
                swatch.classList.add('active');
            } else {
                swatch.classList.remove('active');
            }
        });

        // Broadcast change for other tabs
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    // Immediate Initialization: Apply the saved theme as soon as the script runs to prevent flash
    const saved = localStorage.getItem(THEME_KEY) || defaultTheme;
    root.classList.add(saved);

    document.addEventListener('DOMContentLoaded', function () {
        // Re-apply to body once it exists
        applyTheme(saved);

        // Global Event Delegation for Theme Pickers
        document.body.addEventListener('click', (e) => {
            // Toggle Menu
            const toggle = e.target.closest('[data-toggle]');
            if (toggle) {
                const picker = toggle.closest('[data-theme-picker]');
                if (picker) {
                    const menu = picker.querySelector('.theme-menu');
                    if (menu) menu.classList.toggle('hidden');
                }
            }

            // Select Theme
            const swatch = e.target.closest('[data-theme]');
            if (swatch) {
                const theme = swatch.getAttribute('data-theme');
                applyTheme(theme);
                const menu = swatch.closest('.theme-menu');
                if (menu) menu.classList.add('hidden');
            }
        });

        // Close theme menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[data-theme-picker]')) {
                document.querySelectorAll('.theme-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
    });

    // Handle cross-tab sync
    window.addEventListener('storage', (e) => {
        if (e.key === THEME_KEY) {
            applyTheme(e.newValue);
        }
    });

})();
