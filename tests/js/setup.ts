/**
 * Vitest global setup.
 *
 * `happy-dom` provides `window`, `document`, and `crypto`, but not IndexedDB.
 * `fake-indexeddb/auto` installs a faithful in-memory IndexedDB implementation
 * so the offline-queue module can be tested without a real browser.
 */
import 'fake-indexeddb/auto';
