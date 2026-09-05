import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/scss/public.scss',
                'resources/scss/hr.scss',
                'resources/scss/modules/_asset.scss',
                'resources/scss/modules/_certification.scss',
                'resources/js/app.js',
                'resources/js/page-loader.js',
                'resources/js/csp-hardening.js',
                'resources/js/utils/nik-autofill.js',
                'resources/js/asset.js',
                'resources/js/certification.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    'import',
                    'color-functions',
                    'global-builtin',
                    'if-function',
                    'mixed-decls',
                ],
            },
        },
    },
});
