import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/identity-v2.css',
                'resources/css/gateway-v2.css',
                'resources/css/zumra-hub.css',
                'resources/css/member-space-v2.css',
                'resources/css/auth-v2.css',
                'resources/css/fil-v2.css',
                'resources/css/project-workspace-v2.css',
                'resources/css/projects-directory.css',
                'resources/css/needs-directory.css',
                'resources/css/project-detail.css',
                'resources/css/project-brain.css',
                'resources/js/app.js',
            ],
            refresh: true,
            fonts: [
                // Fontsource ships the font files as npm packages: no CDN fetch at build
                // time or runtime, fully self-hosted per docs/design DECISIONS.md.
                fontsource('Instrument Sans', {
                    weights: [400, 500, 600, 700],
                    optimizedFallbacks: false,
                }),
                fontsource('Instrument Serif', {
                    weights: [400],
                    styles: ['normal', 'italic'],
                    optimizedFallbacks: false,
                }),
                fontsource('IBM Plex Mono', {
                    weights: [400, 500],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
