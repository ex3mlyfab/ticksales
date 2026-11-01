import { fileURLToPath } from 'node:url';

import { defineConfig } from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        setupFiles: ['./tests/js/setup.ts'],
        include: ['resources/js/**/*.test.{ts,tsx}'],
        globals: true,
        css: false,
    },
});
