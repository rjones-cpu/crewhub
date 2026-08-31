import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sidebar: {
                    DEFAULT: '#0B1F3A',
                    dark: '#071628',
                    light: '#123056',
                    muted: '#8BA3C1',
                    border: '#1A3358',
                },
                brand: {
                    DEFAULT: '#2563EB',
                    hover: '#1D4ED8',
                    soft: '#EFF6FF',
                },
                success: {
                    DEFAULT: '#16A34A',
                    soft: '#F0FDF4',
                },
                warning: {
                    DEFAULT: '#EA580C',
                    soft: '#FFF7ED',
                },
                danger: {
                    DEFAULT: '#DC2626',
                    soft: '#FEF2F2',
                },
                journey: {
                    DEFAULT: '#7C3AED',
                    soft: '#F5F3FF',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 0 rgb(0 0 0 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
