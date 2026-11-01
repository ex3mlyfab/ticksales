import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'happy-dom',
        setupFiles: ['./tests/js/setup.ts'],
        include: ['resources/js/**/*.test.{ts,tsx}'],
        globals: true,
        css: false,
    },
});
