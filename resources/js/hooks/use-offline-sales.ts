import { useCallback, useEffect, useRef, useState } from 'react';
import {
    getQueuedCount,
    purgeExpiredSales,
    queueSale,
    syncQueuedSales,
    type QueuedSalePayload,
} from '@/lib/offline-queue';

/**
 * Tracks connectivity and the pending offline-sale queue, replaying queued
 * sales to the server whenever the browser comes back online.
 */
export function useOfflineSales(storeUrl: string) {
    const [isOnline, setIsOnline] = useState<boolean>(
        typeof navigator === 'undefined' ? true : navigator.onLine,
    );
    const [pendingCount, setPendingCount] = useState(0);
    const [syncing, setSyncing] = useState(false);
    const [expiredCount, setExpiredCount] = useState(0);
    const syncingRef = useRef(false);

    const refreshCount = useCallback(async () => {
        try {
            setPendingCount(await getQueuedCount());
        } catch {
            // IndexedDB unavailable (e.g. private mode) — nothing to report.
        }
    }, []);

    const sync = useCallback(async () => {
        if (syncingRef.current) {
            return 0;
        }

        syncingRef.current = true;
        setSyncing(true);

        try {
            const { synced, expired } = await syncQueuedSales(storeUrl);

            if (expired > 0) {
                setExpiredCount((count) => count + expired);
            }

            return synced;
        } finally {
            syncingRef.current = false;
            setSyncing(false);
            await refreshCount();
        }
    }, [storeUrl, refreshCount]);

    useEffect(() => {
        void (async () => {
            const purged = await purgeExpiredSales();

            if (purged > 0) {
                setExpiredCount((count) => count + purged);
            }

            await refreshCount();
        })();
    }, [refreshCount]);

    useEffect(() => {
        const handleOnline = () => {
            setIsOnline(true);
            void sync();
        };
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, [sync]);

    /**
     * Persist a sale locally while offline. Returns true when queued.
     */
    const enqueue = useCallback(
        async (payload: QueuedSalePayload): Promise<boolean> => {
            try {
                await queueSale(payload);
                await refreshCount();

                return true;
            } catch {
                return false;
            }
        },
        [refreshCount],
    );

    return {
        isOnline,
        pendingCount,
        syncing,
        expiredCount,
        sync,
        enqueue,
        refreshCount,
    };
}
