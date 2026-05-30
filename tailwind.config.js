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
            },
            colors: {
                spm: {
                    primary: {
                        50:  '#e6f0eb',
                        100: '#b3d1c2',
                        200: '#80b399',
                        300: '#4d9470',
                        400: '#1a7547',
                        500: '#005533',
                        600: '#004d2d',
                        700: '#003d24',
                        800: '#002e1b',
                        900: '#001f12',
                    },
                    secondary: {
                        50:  '#fff0e6',
                        100: '#ffd1b3',
                        200: '#ffb380',
                        300: '#ff944d',
                        400: '#ff751a',
                        500: '#f36500',
                        600: '#cc5500',
                        700: '#a34400',
                        800: '#7a3300',
                        900: '#522200',
                    },
                    text: {
                        body:    '#1A1A1A',
                        heading: '#111111',
                        muted:   '#667085',
                        subtle:  '#7c8aa0',
                    },
                },
            },
        },
    },

    plugins: [forms],
};
