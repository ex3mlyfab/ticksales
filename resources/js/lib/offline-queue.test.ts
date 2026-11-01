import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import {
    QUEUE_EXPIRY_MS,
    getQueuedCount,
    getQueuedSales,
    purgeExpiredSales,
    queueSale,
    removeQueuedSale,
    syncQueuedSales,
    type QueuedSalePayload,
} from './offline-queue';

const UUID_V4 =
    /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

const basePayload: QueuedSalePayload = {
    branch_id: 'branch-1',
    register_session_id: 'session-1',
    customer_id: null,
    items: [{ product_id: 'product-1', quantity: 2, unit_price: 150 }],
    payments: [{ payment_method_id: 'pay-1', amount: 300 }],
    discount_amount: 0,
    note: null,
};

function clearStore(): Promise<void> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ticksales-offline', 1);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('queued-sales')) {
                db.createObjectStore('queued-sales', {
                    keyPath: 'idempotency_key',
                });
            }
        };
        request.onsuccess = () => {
            const db = request.result;
            const tx = db.transaction('queued-sales', 'readwrite');
            tx.objectStore('queued-sales').clear();
            tx.oncomplete = () => {
                db.close();
                resolve();
            };
            tx.onerror = () => reject(tx.error);
        };
        request.onerror = () => reject(request.error);
    });
}

function backdateQueuedSale(key: string, ageMs: number): Promise<void> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ticksales-offline', 1);
        request.onsuccess = () => {
            const db = request.result;
            const tx = db.transaction('queued-sales', 'readwrite');
            const store = tx.objectStore('queued-sales');
            const get = store.get(key);
            get.onsuccess = () => {
                const record = get.result;
                record.offline_created_at = new Date(
                    Date.now() - ageMs,
                ).toISOString();
                store.put(record);
                tx.oncomplete = () => {
                    db.close();
                    resolve();
                };
            };
            tx.onerror = () => reject(tx.error);
        };
        request.onerror = () => reject(request.error);
    });
}

beforeEach(async () => {
    await clearStore();
    vi.unstubAllGlobals();
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('queueSale', () => {
    it('queues a sale and generates a UUID idempotency key', async () => {
        const record = await queueSale(basePayload);

        expect(record.idempotency_key).toMatch(UUID_V4);
        expect(record.offline_created_at).toBeTruthy();
        expect(record.payload).toEqual(basePayload);

        expect(await getQueuedCount()).toBe(1);
    });

    it('honours a caller-supplied idempotency key', async () => {
        const key = '11111111-1111-4111-8111-111111111111';
        const record = await queueSale(basePayload, key);

        expect(record.idempotency_key).toBe(key);
        expect(await getQueuedSales()).toHaveLength(1);
    });

    it('replaces an existing record with the same key (idempotent put)', async () => {
        const key = '22222222-2222-4222-8222-222222222222';
        await queueSale(basePayload, key);

        const changed = { ...basePayload, note: 'updated' };
        await queueSale(changed, key);

        const all = await getQueuedSales();
        expect(all).toHaveLength(1);
        expect(all[0].payload.note).toBe('updated');
    });
});

describe('getQueuedSales', () => {
    it('returns queued sales oldest-first', async () => {
        const first = await queueSale({ ...basePayload, note: 'first' });
        const second = await queueSale({ ...basePayload, note: 'second' });

        // Rewind the first record so ordering is deterministic even though
        // both were created within the same millisecond.
        await backdateQueuedSale(first.idempotency_key, 5_000);

        const all = await getQueuedSales();
        expect(all.map((sale) => sale.payload.note)).toEqual([
            'first',
            'second',
        ]);
        expect(all[0].idempotency_key).toBe(first.idempotency_key);
        expect(all[1].idempotency_key).toBe(second.idempotency_key);
    });
});

describe('removeQueuedSale', () => {
    it('removes a queued sale by key', async () => {
        const record = await queueSale(basePayload);
        await removeQueuedSale(record.idempotency_key);

        expect(await getQueuedCount()).toBe(0);
    });
});

describe('purgeExpiredSales', () => {
    it('discards only sales older than the expiry window', async () => {
        const fresh = await queueSale({ ...basePayload, note: 'fresh' });
        const stale = await queueSale({ ...basePayload, note: 'stale' });
        await backdateQueuedSale(
            stale.idempotency_key,
            QUEUE_EXPIRY_MS + 60_000,
        );

        const purged = await purgeExpiredSales();

        expect(purged).toBe(1);
        expect(await getQueuedSales()).toHaveLength(1);
        expect((await getQueuedSales())[0].idempotency_key).toBe(
            fresh.idempotency_key,
        );
    });
});

describe('syncQueuedSales', () => {
    it('replays queued sales in order and removes acknowledged ones', async () => {
        const fetchMock = vi.fn().mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetchMock);

        const first = await queueSale({ ...basePayload, note: 'first' });
        const second = await queueSale({ ...basePayload, note: 'second' });

        // Both sales are created within the same millisecond; rewind the
        // first so oldest-first ordering is deterministic, not a coin flip.
        await backdateQueuedSale(first.idempotency_key, 5_000);

        const result = await syncQueuedSales('/api/v1/sales');

        expect(result).toEqual({ synced: 2, expired: 0 });
        expect(fetchMock).toHaveBeenCalledTimes(2);

        const firstBody = JSON.parse(fetchMock.mock.calls[0][1].body);
        expect(firstBody.idempotency_key).toBe(first.idempotency_key);
        expect(firstBody.synced_from_offline).toBe(true);
        expect(firstBody.offline_created_at).toBeTruthy();

        const secondBody = JSON.parse(fetchMock.mock.calls[1][1].body);
        expect(secondBody.idempotency_key).toBe(second.idempotency_key);

        // Both sales are removed after a successful sync.
        expect(await getQueuedCount()).toBe(0);
    });

    it('stops on a failed request to preserve ordering', async () => {
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce({ ok: false })
            .mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetchMock);

        await queueSale({ ...basePayload, note: 'first' });
        await queueSale({ ...basePayload, note: 'second' });

        const result = await syncQueuedSales('/api/v1/sales');

        expect(result.synced).toBe(0);
        expect(fetchMock).toHaveBeenCalledTimes(1);
        // Nothing removed: the failing sale and everything after stays queued.
        expect(await getQueuedCount()).toBe(2);
    });

    it('discards expired sales instead of replaying them', async () => {
        const fetchMock = vi.fn().mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetchMock);

        const stale = await queueSale({ ...basePayload, note: 'stale' });
        const fresh = await queueSale({ ...basePayload, note: 'fresh' });
        await backdateQueuedSale(
            stale.idempotency_key,
            QUEUE_EXPIRY_MS + 60_000,
        );

        const result = await syncQueuedSales('/api/v1/sales');

        expect(result).toEqual({ synced: 1, expired: 1 });
        // Only the fresh sale is posted to the server.
        expect(fetchMock).toHaveBeenCalledTimes(1);
        const posted = JSON.parse(fetchMock.mock.calls[0][1].body);
        expect(posted.idempotency_key).toBe(fresh.idempotency_key);
        expect(await getQueuedCount()).toBe(0);
    });
});
