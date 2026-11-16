import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                },
            colors: {
                // Principal de Clara 2.0 (azul marino). Reemplaza los antiguos blue/indigo.
                accent: {
                    50: '#f3f5f9', 100: '#e6eaf2', 200: '#cbd3e3', 300: '#9eabc7',
                    400: '#6b7ca3', 500: '#46578a', 600: '#2b3a67', 700: '#1f2a44',
                    800: '#182136', 900: '#121a2b', 950: '#0b101c',
                },
            },
        },
    },

    plugins: [forms],
};
