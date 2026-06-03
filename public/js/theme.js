/**
 * Dark-mode theme toggle.
 *
 * The initial theme is applied inline in the document <head> (before paint) to
 * avoid a flash. This script only wires the toggle button(s) and persists the
 * user's choice to localStorage.
 */
(function () {
    'use strict';

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    function setTheme(dark) {
        document.documentElement.classList.toggle('dark', dark);
        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            /* storage unavailable — runtime toggle still works */
        }
    }

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-theme-toggle]');
        if (toggle) {
            setTheme(!isDark());
        }
    });
})();
