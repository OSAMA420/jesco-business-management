import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // JESCO admin panel is light-themed only; without this, `dark:` classes
    // left over from Breeze scaffolding activate on visitors with OS-level
    // dark mode and produce low-contrast, unstyled-looking pages.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                script: ['"Dancing Script"', 'cursive'],
            },
            colors: {
                jesco: {
                    50: '#fef2f2',
                    100: '#fee2e1',
                    200: '#fecaca',
                    300: '#fca5a4',
                    400: '#f87270',
                    500: '#ee423f',
                    600: '#e90f08',
                    700: '#c40d07',
                    800: '#a10f0a',
                    900: '#85120d',
                    950: '#480604',
                },
            },
        },
    },

    plugins: [forms],
};
