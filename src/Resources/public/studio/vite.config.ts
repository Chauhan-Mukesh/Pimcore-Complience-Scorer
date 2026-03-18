import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [react()],
    build: {
        lib: {
            entry: resolve(__dirname, 'src/index.ts'),
            name: 'PimcoreMarketReadinessShield',
            fileName: 'market-readiness-shield',
            formats: ['iife'],
        },
        rollupOptions: {
            // React is bundled into the IIFE so the script works standalone
            // when loaded via getJsPaths() without any external dependencies.
        },
        outDir: 'dist',
        sourcemap: 'hidden',
        minify: true,
    },
});
