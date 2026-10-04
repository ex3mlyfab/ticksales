# PWA Architecture

## Overview

TickSales is designed as a **Progressive Web App (PWA)** that can be installed on mobile and desktop devices. The PWA prioritizes:

1. **Installability** — Appears as a native app on home screen / taskbar
2. **Offline awareness** — Clearly communicates connectivity status
3. **Offline POS operation** — Cashiers can complete sales during brief connectivity loss
4. **Safe synchronization** — Offline transactions sync idempotently without duplication
5. **Cached shell** — App loads instantly even offline (except initial install)

---

## PWA Requirements Checklist

| Requirement | Status | Notes |
|-------------|--------|-------|
| Web App Manifest | Planned | `public/manifest.webmanifest` |
| Service Worker | Planned | Via `vite-plugin-pwa` (Workbox) |
| HTTPS | Required in production | |
| App Shell Cache | Planned | Static assets cached |
| Installability prompt | Planned | `BeforeInstallPromptEvent` |
| Icons (multiple sizes) | Planned | 192px, 512px minimum |
| Splash screens | Planned | |
| Offline fallback page | Planned | |
| Network status indicator | Planned | React hook + UI component |
| Offline transaction queue | Planned | IndexedDB via idb library |
| Sync on reconnect | Planned | Background sync or manual trigger |
| Push notifications | Future | Not in initial scope |

---

## Service Worker Strategy

Using **Workbox** (via `vite-plugin-pwa`) with the following caching strategies:

### App Shell (Static Assets)
- **Strategy:** Cache First → Network Update
- **Assets:** JS bundles, CSS, fonts, icons
- **Behavior:** Serves cached version immediately; updates in background

### API Requests
- **Strategy:** Network First → Cache Fallback (read-only endpoints)
- **Cached endpoints:** `/api/v1/products`, `/api/v1/payment-methods`, `/api/v1/product-categories`
- **Non-cached:** Write endpoints (sales, etc.) — must go to network or queue

### Navigation (HTML)
- **Strategy:** Network First → Offline Fallback Page
- **Fallback:** A static "You're offline" page with sync status

---

## Web App Manifest

```json
{
    "name": "TickSales POS",
    "short_name": "TickSales",
    "description": "Point of Sale Management System",
    "start_url": "/dashboard",
    "display": "standalone",
    "orientation": "any",
    "background_color": "#ffffff",
    "theme_color": "#0f172a",
    "icons": [
        { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
        { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
        { "src": "/icons/icon-512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
    ],
    "shortcuts": [
        {
            "name": "New Sale",
            "url": "/pos/new",
            "icons": [{ "src": "/icons/shortcut-pos.png", "sizes": "96x96" }]
        }
    ],
    "screenshots": [ ]
}
```

The `theme_color` is dynamically updated to match the tenant's primary color where possible.

---

## Offline Transaction Queue

### Architecture

```
Cashier (offline)
    ↓ Create sale
    ↓ Validate locally (basic checks)
    ↓ Store in IndexedDB (queue)
    ↓ Display as "Pending Sync" in UI

Connectivity restored
    ↓ Background sync triggered (or manual)
    ↓ For each queued transaction:
        ↓ POST to /api/v1/sales with idempotency_key
        ↓ Server validates:
            - idempotency_key not already processed
            - User had open register session at offline_created_at
            - Inventory was (best-effort) sufficient
        ↓ Success → mark transaction as "Synced"
        ↓ Failure → mark as "Sync Failed", show error
```

### Local Transaction Schema (IndexedDB)

```typescript
interface OfflineSale {
    localId: string;           // Client-generated ULID
    idempotencyKey: string;    // UUID v4 — sent to server
    tenantId: string;
    branchId: string;
    registerSessionId: string;
    customerId: string | null;
    items: OfflineSaleItem[];
    payments: OfflinePayment[];
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    totalAmount: number;
    note: string | null;
    soldBy: number;            // User ID
    offlineCreatedAt: string;  // ISO 8601
    syncStatus: 'pending' | 'syncing' | 'synced' | 'failed' | 'conflict';
    syncedAt: string | null;
    syncError: string | null;
    serverId: string | null;   // Returned by server on success
}

interface OfflineSaleItem {
    productId: string;
    variantId: string | null;
    name: string;              // Snapshot
    sku: string | null;
    quantity: number;
    unitPrice: number;
    discountAmount: number;
    taxAmount: number;
    totalAmount: number;
}
```

### Idempotency Safety

- Every offline sale has a **UUID v4 `idempotency_key`** generated client-side
- The server stores processed idempotency keys in `sales.idempotency_key` (unique index)
- Duplicate submissions return the original sale response (HTTP 200) rather than creating a duplicate
- Keys are considered expired after 48 hours (server will reject very old offline sales)

### Conflict Handling

If a sale cannot be synced:
- Mark as `sync_failed` or `conflict`
- Show clear error to cashier with the reason
- Allow cashier to review and either retry or void the transaction
- Never silently discard a failed sync

---

## Network Status Indicator

A persistent `NetworkStatusBanner` component displayed in the app shell:

```
┌─────────────────────────────────────────────────────┐
│ 🔴 You're offline. New sales will queue for sync.   │
│ 🟡 Syncing 3 pending transactions...                │
│ 🟢 All transactions synced.                         │
│ 🔴 Sync failed. Tap to retry. (2 transactions)      │
└─────────────────────────────────────────────────────┘
```

### Network Status Hook

```typescript
function useNetworkStatus() {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [pendingCount, setPendingCount] = useState(0);
    const [syncStatus, setSyncStatus] = useState<'idle' | 'syncing' | 'failed'>('idle');

    // Listen to online/offline events
    // Poll IndexedDB for pending count
    // Trigger sync when coming online

    return { isOnline, pendingCount, syncStatus };
}
```

---

## Offline Data Caching

The following data is cached locally for offline POS operation:

| Data | Cache | TTL | Notes |
|------|-------|-----|-------|
| Products (active) | IndexedDB | 24h | Full catalogue |
| Product categories | IndexedDB | 24h | |
| Payment methods | IndexedDB | 24h | |
| Current user | IndexedDB | Session | |
| Active branch | IndexedDB | Session | |
| Register session | IndexedDB | Session | |
| Customers (recent) | IndexedDB | 24h | Recent 500 |

A background refresh updates the cache every 15 minutes while online.

---

## PWA Install Prompt

A custom `InstallPWABanner` component handles the `BeforeInstallPromptEvent`:

```typescript
// Deferred prompt stored in state
// Show "Add to Home Screen" button in navigation
// Dismiss and remember for 7 days if declined
```

---

## Sync on Reconnect

When the browser detects connectivity:

1. Service worker `sync` event fires (Background Sync API where supported)
2. Fallback: React `online` event listener triggers sync
3. Process queued transactions in FIFO order
4. Update IndexedDB sync status after each

---

## Push Notifications (Future)

Low stock alerts, payment received notifications, and system alerts are planned for a future phase using the Push API and Laravel Notifications. This requires:
- Push subscription management
- Web Push credentials in environment
- Laravel notification channels

This is **explicitly out of scope for the initial implementation**.

---

## Vite PWA Plugin Configuration

```typescript
// vite.config.ts (planned addition)
import { VitePWA } from 'vite-plugin-pwa'

VitePWA({
    registerType: 'autoUpdate',
    manifest: { /* ... */ },
    workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        runtimeCaching: [
            {
                urlPattern: /^\/api\/v1\/(products|payment-methods|product-categories)/,
                handler: 'NetworkFirst',
                options: {
                    cacheName: 'api-cache',
                    expiration: { maxAgeSeconds: 86400 },
                },
            },
        ],
    },
})
```

---

## Sync Status UI States

| Status | UI | Color |
|--------|----|-------|
| Online, no queue | Silent | — |
| Offline | Banner: "You're offline" | Amber |
| Offline, has queue | Banner: "N transactions pending sync" | Amber |
| Syncing | Banner with spinner: "Syncing..." | Blue |
| Sync complete | Toast: "All synced" (auto-dismiss) | Green |
| Sync failed | Persistent banner + Retry button | Red |
| Conflict | Alert + Review button | Orange |
