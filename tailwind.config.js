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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Space Grotesk"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Acento de Clara (petróleo). Reemplaza los antiguos blue/indigo.
                accent: {
                    50: '#effaf9', 100: '#d6f1ee', 200: '#ade2dc', 300: '#7fcdc5',
                    400: '#6cc4bd', 500: '#2f948f', 600: '#26807c', 700: '#1f6b68',
                    800: '#1b5654', 900: '#163f3e', 950: '#0b2221',
                },
            },
        },
    },

    plugins: [forms],
};
