# Architecture — TickSales POS Platform

## Overview

TickSales is a **multi-tenant, cloud-based Point-of-Sale (POS) Management Platform** for small and medium-sized businesses. A single installation serves multiple independent tenants (businesses) with full data isolation, per-tenant branding, and role-based access control.

The architecture is a **modular Laravel monolith** — intentionally avoiding microservices complexity while preserving the ability to extract services if scale later requires it.

---

## Technology Stack

### Backend
| Concern | Technology |
|---------|-----------|
| Runtime | PHP 8.5 |
| Framework | Laravel 13.34 |
| Database | MySQL / MariaDB (SQLite in development) |
| Authentication | Laravel Fortify + Passkeys + 2FA |
| ORM | Laravel Eloquent |
| Authorization | Laravel Policies + Gates |
| Background Jobs | Laravel Queue (database driver) |
| Notifications | Laravel Notifications |
| Caching | Laravel Cache (database driver → Redis in production) |
| Sessions | Laravel Sessions (database driver) |
| File Storage | Laravel Filesystem |
| API Auth | Laravel Sanctum (mobile clients) |

### Frontend
| Concern | Technology |
|---------|-----------|
| UI Framework | React 19 (strict mode) |
| Language | TypeScript 5.7 (strict) |
| SPA Bridge | Inertia.js v3 |
| Styling | Tailwind CSS v4 |
| Component Library | shadcn/ui (New York style, Radix UI primitives) |
| Icons | Lucide React |
| Notifications | Sonner (toasts) |
| Route Helpers | Laravel Wayfinder (typed route functions) |
| Bundler | Vite 8 (via vite-plus) |
| Fonts | Instrument Sans (Bunny Fonts) |
| Compiler | React Compiler (babel-plugin-react-compiler) |

### Mobile API
| Concern | Technology |
|---------|-----------|
| Protocol | REST / JSON |
| Auth | Laravel Sanctum (token-based) |
| Versioning | `/api/v1/` prefix |

### Progressive Web App
| Concern | Technology |
|---------|-----------|
| Service Worker | Workbox (vite-plugin-pwa) |
| Manifest | Web App Manifest |
| Offline Queue | IndexedDB + sync |
| Install Prompt | BeforeInstallPromptEvent |

---

## High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        TickSales Platform                        │
│                                                                  │
│  ┌──────────────┐   ┌─────────────────┐   ┌──────────────────┐ │
│  │   Web / PWA  │   │   Mobile Apps   │   │  Platform Admin  │ │
│  │  (React +    │   │  (iOS/Android)  │   │  (Super Admin)   │ │
│  │   Inertia)   │   │   REST API v1   │   │                  │ │
│  └──────┬───────┘   └────────┬────────┘   └────────┬─────────┘ │
│         │                    │                      │           │
│  ┌──────▼────────────────────▼──────────────────────▼─────────┐ │
│  │                    Laravel Application                       │ │
│  │                                                              │ │
│  │  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │ │
│  │  │  Web Routes  │  │  API Routes  │  │  Console Routes  │   │ │
│  │  │  (Inertia)   │  │  /api/v1/    │  │  (Scheduler)     │   │ │
│  │  └──────┬───────┘  └──────┬───────┘  └────────┬─────────┘   │ │
│  │         │                 │                    │             │ │
│  │  ┌──────▼─────────────────▼────────────────────▼──────────┐  │ │
│  │  │                   Middleware Stack                       │  │ │
│  │  │  Auth │ TenantContext │ BranchContext │ Permission      │  │ │
│  │  └──────────────────────┬─────────────────────────────────┘  │ │
│  │                         │                                     │ │
│  │  ┌──────────────────────▼──────────────────────────────────┐  │ │
│  │  │               Application Layer                          │  │ │
│  │  │  Controllers → Actions/Services → Models → Events       │  │ │
│  │  └──────────────────────┬──────────────────────────────────┘  │ │
│  │                         │                                     │ │
│  │  ┌──────────────────────▼──────────────────────────────────┐  │ │
│  │  │               Data Layer                                  │  │ │
│  │  │  Eloquent ORM │ Query Builder │ Cache │ Queue            │  │ │
│  │  └──────────────────────┬──────────────────────────────────┘  │ │
│  │                         │                                     │ │
│  └─────────────────────────┼─────────────────────────────────────┘ │
│                            │                                       │
│  ┌─────────────────────────▼──────────────────────────────────┐   │
│  │                     Database (MySQL)                         │   │
│  │  Platform Tables │ Tenant Tables │ Audit Logs               │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Repository Discovery Summary

### Current State (as of 2026-10-04)

The repository is a **fresh Laravel 13 + React + Inertia starter kit**. The following is already in place:

| Area | Status | Notes |
|------|--------|-------|
| Laravel Framework | ✅ 13.34 | Fully configured |
| PHP | ✅ 8.5 | Latest stable |
| Inertia.js | ✅ v3.0 | SSR-capable, deferred props, instant visits |
| React | ✅ 19 | Strict mode, React Compiler enabled |
| TypeScript | ✅ 5.7 | Strict mode |
| Tailwind CSS | ✅ v4 | With custom OKLCH color tokens |
| shadcn/ui | ✅ New York | Sidebar, Sheet, Dialog, Select, Tooltip, Sonner etc. |
| Lucide Icons | ✅ | |
| Wayfinder | ✅ | Typed route functions generated |
| Fortify | ✅ | Login, Register, Reset, Verify Email, 2FA, Passkeys |
| Passkeys / WebAuthn | ✅ | web-auth/webauthn-lib installed |
| App Layout | ✅ | Sidebar layout with collapsible sidebar |
| Auth Layout | ✅ | Card, simple, split variants |
| Settings Routes | ✅ | Profile, Security, Appearance |
| Sessions (DB) | ✅ | |
| Queue (DB) | ✅ | |
| Cache (DB) | ✅ | |
| Pest Testing | ✅ | v5.3, auth tests exist |
| PHPStan / Larastan | ✅ | Level configured |
| Laravel Pint | ✅ | Code formatting |
| User Model | ✅ | With 2FA, Passkeys, MustVerifyEmail |
| ULID Primary Keys | ✅ | Project skill enforces ULID on all new models |
| Multi-tenancy | ❌ | Not implemented |
| RBAC | ❌ | Not implemented |
| POS Domain | ❌ | Not implemented |
| API v1 | ❌ | Not implemented |
| PWA | ❌ | Not implemented |
| Audit Logging | ❌ | Not implemented |
| Branding System | ❌ | Not implemented |

**Key Observations:**
- The `users` table currently uses auto-incrementing integer ID. New application models will use ULID string PKs per the project skill. The existing `users` table migration is a framework migration and is exempt per the ULID skill (Rule 4).
- No `docs/` directory existed previously.
- No `routes/api.php` exists yet.
- The design token system (OKLCH CSS variables) is already excellent and provides the foundation for tenant branding.
- The sidebar layout is polished and production-ready.

---

## Directory Structure

### Current (starter)
```
ticksales/
├── app/
│   ├── Actions/Fortify/
│   ├── Concerns/
│   ├── Http/Controllers/Settings/
│   ├── Http/Middleware/
│   ├── Http/Requests/Settings/
│   ├── Models/User.php
│   └── Providers/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/app.css
│   ├── js/
│   │   ├── app.tsx
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── layouts/
│   │   ├── lib/
│   │   ├── pages/
│   │   ├── routes/       ← Wayfinder generated
│   │   ├── types/
│   │   └── wayfinder/
│   └── views/app.blade.php
├── routes/
│   ├── console.php
│   ├── settings.php
│   └── web.php
└── tests/
    ├── Feature/Auth/
    ├── Feature/Settings/
    └── Unit/
```

### Target (after full implementation)
```
ticksales/
├── app/
│   ├── Actions/
│   │   ├── Fortify/
│   │   ├── Inventory/
│   │   ├── POS/
│   │   ├── Purchasing/
│   │   └── Tenant/
│   ├── Concerns/
│   ├── Domain/
│   ├── Enums/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/        ← Mobile API
│   │   │   └── Web/           ← Inertia controllers
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/         ← API Resources
│   ├── Jobs/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Providers/
│   ├── Services/
│   └── Support/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/                     ← This directory
├── resources/
│   ├── css/app.css
│   └── js/
│       ├── app.tsx
│       ├── components/       ← Shared/reusable components
│       ├── features/         ← Feature-specific components
│       ├── hooks/
│       ├── layouts/
│       ├── lib/
│       ├── pages/
│       ├── routes/           ← Wayfinder generated
│       ├── types/
│       └── wayfinder/
├── routes/
│   ├── api.php              ← Mobile API routes
│   ├── console.php
│   ├── settings.php
│   └── web.php
└── tests/
    ├── Feature/
    │   ├── Auth/
    │   ├── Api/
    │   ├── POS/
    │   ├── Inventory/
    │   ├── Settings/
    │   └── Tenant/
    └── Unit/
```

---

## Application Architecture Patterns

### Controller → Action → Model Pattern
Controllers stay thin. Domain logic lives in `Actions/` (single-purpose classes) or `Services/` (stateful multi-step processes). Models handle Eloquent relationships and scopes only.

```
HTTP Request
    → FormRequest (validate)
    → Controller (orchestrate)
    → Action (domain logic)
    → Model (persistence)
    → Event (side effects)
    → Response (Inertia / JSON)
```

### Tenant Context
Every authenticated request carries a resolved `Tenant` object. A middleware resolves the active tenant from the authenticated user's membership and injects it into the request lifecycle. All tenant-scoped queries use an Eloquent global scope or explicit `where('tenant_id', ...)` clauses.

### Authorization Hierarchy
```
Platform Super Admin
    └── Tenant Admin
            └── Branch Manager
                    └── Cashier / Staff
```

Server-side enforcement via Laravel Policies. Frontend enforcement via Inertia shared `permissions` prop (UI visibility only — never trusted for authorization).

---

## Key Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Multi-tenancy strategy | Shared DB / tenant_id scoping | Simpler ops, easier backups, sufficient isolation for SMB SaaS |
| Primary keys | ULID strings | Project enforced skill; globally unique, URL-safe, sortable |
| Mobile API auth | Laravel Sanctum tokens | Stateless, revocable, device-level tokens |
| Offline strategy | IndexedDB queue + sync | Safe offline POS without pretending transactions are committed |
| Branding | CSS custom properties | Zero-recompile tenant theming |
| RBAC | Custom roles/permissions (no Spatie) | Full control, simpler than dependency on external package |
| Inventory accounting | Ledger-style transactions | Full audit trail, reconstructable history |
| PWA | Vite PWA plugin (Workbox) | Proven toolchain, service worker generation |

---

## Security Posture

See [`SECURITY.md`](./SECURITY.md) for full details. Key principles:
- Tenant isolation enforced at query level (never trusted from client input)
- All authorization server-side via Policies
- CSRF protection on all state-changing web routes
- Sanctum token auth for API (no session bridging)
- Rate limiting on authentication endpoints (Fortify)
- Audit trail for critical operations
- No sensitive data in Inertia shared props

---

## Performance Considerations

- Eloquent eager loading enforced to prevent N+1 queries
- Database indexes on `tenant_id`, `branch_id`, and frequently filtered columns
- Inertia deferred props for expensive dashboard widgets
- Cache for tenant branding, permissions, and frequently read configuration
- API pagination mandatory (no unbounded collection responses)
- Background jobs for: receipt generation, notifications, report exports, sync operations

---

## Related Documents

| Document | Purpose |
|----------|---------|
| [`MULTI_TENANCY.md`](./MULTI_TENANCY.md) | Tenant isolation strategy and implementation |
| [`DATABASE_DESIGN.md`](./DATABASE_DESIGN.md) | ERD, schema, relationships |
| [`API_ARCHITECTURE.md`](./API_ARCHITECTURE.md) | Mobile API design and conventions |
| [`SECURITY.md`](./SECURITY.md) | Security controls and threat model |
| [`DESIGN_SYSTEM.md`](./DESIGN_SYSTEM.md) | UI components, tokens, patterns |
| [`PWA_ARCHITECTURE.md`](./PWA_ARCHITECTURE.md) | PWA, offline, and sync strategy |
| [`IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md) | Phase-by-phase implementation plan |
| [`TASK_LOG.md`](./TASK_LOG.md) | Living implementation journal |
