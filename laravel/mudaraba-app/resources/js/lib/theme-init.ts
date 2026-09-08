/**
 * Anti-FOUC theme bootstrap.
 * Inline this script in the HTML <head> (before body paint) to set the
 * `dark` class on <html> based on localStorage / system preference.
 */
export const themeInitScript = `
(function() {
    try {
        var stored = localStorage.getItem('mudaraba-theme') || 'system';
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        var isDark = stored === 'dark' || (stored === 'system' && prefersDark);
        if (isDark) document.documentElement.classList.add('dark');
    } catch (e) {}
})();
`;
