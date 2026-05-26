import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        react(),
        tailwindcss(),
    ],
    publicDir: false,
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        outDir: 'public/vendor/heimdall',
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(__dirname, 'resources/js/app.tsx'),
            output: {
                entryFileNames: 'app.js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: (info) => {
                    if (info.name?.endsWith('.css')) return 'app.css';
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});
