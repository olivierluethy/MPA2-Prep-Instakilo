/** @type {import('tailwindcss').Config} */
module.exports = {
    // Class-based dark mode: toggled by adding `dark` to <html>.
    darkMode: 'class',
    // Scan every place a class name can appear so the purge keeps real classes.
    content: [
        // All PHP is scanned (not just Views) so class names emitted from
        // presenter classes like app/Core/NotificationType.php are kept.
        './app/**/*.php',
        './public/js/**/*.js',
    ],
    // Safelist classes that are only ever added dynamically from JS.
    safelist: [
        'btn-primary',
        'btn-secondary',
        'opacity-0',
        'opacity-50',
        'opacity-100',
        'bg-indigo-50',
        'border-indigo-500',
        'dark:bg-indigo-950/40',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
