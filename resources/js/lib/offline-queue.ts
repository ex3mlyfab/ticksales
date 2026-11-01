/**
 * Dependency-free IndexedDB queue for offline POS sales.
 *
 * Sales created while the device is offline are stored locally and replayed
 * to the server once connectivity returns. Each queued sale carries a stable
 * idempotency key so a replay can never create a duplicate server-side sale.
 */

const DB_NAME = 'ticksales-offline';
const DB_VERSION = 1;
const STORE_NAME = 'queued-sales';

/**
 * Queued sales older than this are discarded on sync instead of being replayed.
 * This mirrors the 48h expiry from the implementation plan's risk register so
 * stale offline orders cannot conflict with server-side reconciliation.
 */
export const QUEUE_EXPIRY_MS = 48 * 60 * 60 * 1000;

export interface QueuedSalePayload {
    branch_id?: string | null;
    register_session_id?: string | null;
    customer_id?: string | null;
    items: Array<{
        product_id: string;
        name?: string;
        quantity: number;
        unit_price: number;
        tax_rate?: number;
        discount_amount?: number;
    }>;
    payments: Array<{
        payment_method_id: string;
        amount: number;
        reference?: string;
    }>;
    discount_amount?: number;
    note?: string | null;
}

export interface QueuedSale {
    idempotency_key: string;
    offline_created_at: string;
    payload: QueuedSalePayload;
}

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, {
                    keyPath: 'idempotency_key',
                });
                store.createIndex('offline_created_at', 'offline_created_at');
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Queue a sale for later sync. A stable key is generated when none is given.
 */
export async function queueSale(
    payload: QueuedSalePayload,
    idempotencyKey?: string,
): Promise<QueuedSale> {
    const record: QueuedSale = {
        idempotency_key: idempotencyKey ?? createIdempotencyKey(),
        offline_created_at: new Date().toISOString(),
        payload,
    };

    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).put(record);
        tx.oncomplete = () => {
            db.close();
            resolve(record);
        };
        tx.onerror = () => {
            db.close();
            reject(tx.error);
        };
    });
}

/**
 * Return every queued sale, oldest first.
 */
export async function getQueuedSales(): Promise<QueuedSale[]> {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const request = tx.objectStore(STORE_NAME).getAll();
        request.onsuccess = () => {
            db.close();
            const rows = (request.result as QueuedSale[]).sort((a, b) =>
                a.offline_created_at.localeCompare(b.offline_created_at),
            );
            resolve(rows);
        };
        request.onerror = () => {
            db.close();
            reject(request.error);
        };
    });
}

/**
 * Remove a sale from the queue once it has synced successfully.
 */
export async function removeQueuedSale(idempotencyKey: string): Promise<void> {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).delete(idempotencyKey);
        tx.oncomplete = () => {
            db.close();
            resolve();
        };
        tx.onerror = () => {
            db.close();
            reject(tx.error);
        };
    });
}

/**
 * Count of queued sales waiting to sync.
 */
export async function getQueuedCount(): Promise<number> {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const request = tx.objectStore(STORE_NAME).count();
        request.onsuccess = () => {
            db.close();
            resolve(request.result);
        };
        request.onerror = () => {
            db.close();
            reject(request.error);
        };
    });
}

/**
 * Replay every queued sale to the store endpoint in FIFO order.
 *
 * The server enforces idempotency via `idempotency_key`, so a sale that was
 * already persisted (or partially synced) will never be duplicated. Only
 * successfully acknowledged sales are removed from the queue; a failure stops
 * the run so ordering is preserved on the next attempt.
 *
 * Sales older than `QUEUE_EXPIRY_MS` are discarded rather than replayed, to
 * prevent stale offline orders from conflicting with server-side data.
 *
 * @returns the number of sales synced and the number of expired (discarded) sales.
 */
export async function syncQueuedSales(
    storeUrl: string,
): Promise<{ synced: number; expired: number }> {
    const queued = await getQueuedSales();
    let synced = 0;
    let expired = 0;
    const now = Date.now();

    for (const sale of queued) {
        const created = new Date(sale.offline_created_at).getTime();

        if (now - created > QUEUE_EXPIRY_MS) {
            await removeQueuedSale(sale.idempotency_key);
            expired += 1;
            continue;
        }

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    ...sale.payload,
                    idempotency_key: sale.idempotency_key,
                    synced_from_offline: true,
                    offline_created_at: sale.offline_created_at,
                }),
            });

            if (!response.ok) {
                // Stop so ordering is preserved; retry on next reconnect.
                break;
            }

            await removeQueuedSale(sale.idempotency_key);
            synced += 1;
        } catch {
            break;
        }
    }

    return { synced, expired };
}

/**
 * Remove queued sales older than the expiry window without attempting a sync.
 *
 * Ensures stale offline orders are discarded even if the device never regains
 * connectivity while they are pending. Returns the number of records dropped.
 */
export async function purgeExpiredSales(): Promise<number> {
    const queued = await getQueuedSales();
    const now = Date.now();
    const expired = queued.filter(
        (sale) =>
            now - new Date(sale.offline_created_at).getTime() > QUEUE_EXPIRY_MS,
    );

    for (const sale of expired) {
        await removeQueuedSale(sale.idempotency_key);
    }

    return expired.length;
}

function getCsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Generate an RFC 4122 v4 UUID for the idempotency key.
 *
 * The `sales.idempotency_key` column is a `uuid`, so keys must be real UUIDs
 * (not the `offline-*` prefix used previously) to stay within `char(36)` and
 * remain valid on MySQL. Falls back to a seeded generator when the browser
 * does not provide `crypto.randomUUID` (non-secure contexts).
 */
function createIdempotencyKey(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0;
        const value = char === 'x' ? random : (random & 0x3) | 0x8;

        return value.toString(16);
    });
}
