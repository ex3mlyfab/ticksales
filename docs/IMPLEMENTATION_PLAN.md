# Implementation Plan

## Project: TickSales POS Management Platform

**Target Stack:** Laravel 13 / PHP 8.5 / React 19 / TypeScript / Inertia v3 / Tailwind v4 / shadcn/ui

---

## Phase Overview

| Phase | Name                         | Key Deliverables                                              |
| ----- | ---------------------------- | ------------------------------------------------------------- |
| 0     | Repository Discovery         | Architecture docs (this file)                                 |
| 1     | Foundation                   | Auth, User model, Platform Admin, Tenant model, RBAC          |
| 2     | Multi-Tenancy                | Tenant context, isolation, branding, settings                 |
| 3     | Product Catalogue            | Products, categories, brands, units, variants, tax, discounts |
| 4     | Inventory                    | Stock management, adjustments, transfers, movement ledger     |
| 5     | Parties                      | Customers, customer groups, suppliers                         |
| 6     | Purchasing                   | Purchase orders, purchases, purchase returns                  |
| 7     | POS                          | Payment methods, registers, sessions, sales, receipts         |
| 8     | Returns & Refunds            | Sale returns, purchase returns                                |
| 9     | Expenses                     | Expenses, categories, cash movements                          |
| 10    | Dashboard & Reports          | KPI dashboard, sales reports, inventory reports               |
| 11    | PWA                          | Service worker, offline queue, manifest, install prompt       |
| 12    | API v1                       | Mobile REST API, Sanctum auth, versioned endpoints            |
| 13    | Testing                      | Feature tests, security tests, unit tests                     |
| 14    | Performance & Security Audit | N+1 fixes, indexing, CSP, penetration review                  |
| 15    | Production Readiness         | Documentation, seeders, deployment prep                       |
| 16    | Tenant Online Storefront     | Public catalog, online orders, payment, fulfillment, tracking |

---

## Phase 0 — Repository Discovery ✅

**Objective:** Understand the existing codebase and produce architecture documentation.

**Deliverables:**

- [x] `docs/ARCHITECTURE.md`
- [x] `docs/MULTI_TENANCY.md`
- [x] `docs/DATABASE_DESIGN.md`
- [x] `docs/API_ARCHITECTURE.md`
- [x] `docs/DESIGN_SYSTEM.md`
- [x] `docs/SECURITY.md`
- [x] `docs/PWA_ARCHITECTURE.md`
- [x] `docs/IMPLEMENTATION_PLAN.md`
- [x] `docs/TASK_LOG.md`

---

## Phase 1 — Foundation

### 1.1 — Users Table Enhancement

**Objective:** Add platform admin flag and profile fields to users.

**Database changes:**

```sql
ALTER TABLE users ADD COLUMN is_platform_admin boolean DEFAULT false;
ALTER TABLE users ADD COLUMN avatar varchar(255) nullable;
ALTER TABLE users ADD COLUMN phone varchar(50) nullable;
```

**Files to create/modify:**

- `database/migrations/YYYY_MM_DD_add_platform_admin_to_users_table.php`
- `app/Models/User.php` — add `is_platform_admin`, `avatar`, `phone` properties

**Acceptance criteria:**

- Platform admin flag is set on the seed admin user
- User model correctly reflects new fields

---

### 1.2 — Tenant Model & Migration

**Objective:** Create the Tenant model and migration.

**Database changes:** `tenants` table (see DATABASE_DESIGN.md)

**Files to create:**

- `database/migrations/YYYY_create_tenants_table.php`
- `app/Models/Tenant.php` (HasUlids, HasFactory, SoftDeletes)
- `database/factories/TenantFactory.php`

**Acceptance criteria:**

- Migration runs without error
- Factory creates valid tenants
- Test: Can create and retrieve a tenant

---

### 1.3 — Tenant Users (Membership)

**Objective:** Link users to tenants with role context.

**Database changes:** `tenant_users` table

**Files to create:**

- `database/migrations/YYYY_create_tenant_users_table.php`
- `app/Models/TenantUser.php`
- Update `app/Models/User.php` — `tenants()` relationship
- Update `app/Models/Tenant.php` — `users()` relationship

**Acceptance criteria:**

- User can belong to multiple tenants
- TenantUser pivot records role and active status

---

### 1.4 — Roles & Permissions

**Objective:** Create the RBAC foundation.

**Database changes:** `roles`, `permissions`, `role_permissions` tables

**Files to create:**

- `database/migrations/YYYY_create_roles_table.php`
- `database/migrations/YYYY_create_permissions_table.php`
- `database/migrations/YYYY_create_role_permissions_table.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `database/seeders/PermissionSeeder.php` — seed all permissions
- `database/seeders/RoleSeeder.php` — seed system roles

**Permissions to seed:**

```
dashboard.view
products.view, products.create, products.update, products.delete
inventory.view, inventory.adjust, inventory.transfer
customers.view, customers.create, customers.update, customers.delete
suppliers.view, suppliers.create, suppliers.update, suppliers.delete
sales.view, sales.create, sales.update, sales.refund, sales.void
purchases.view, purchases.create, purchases.update, purchases.delete
expenses.view, expenses.create, expenses.update, expenses.delete
registers.view, registers.open, registers.close
reports.view, reports.export
users.view, users.create, users.update, users.delete
roles.view, roles.create, roles.update, roles.delete
settings.view, settings.update
branches.view, branches.create, branches.update
```

**System roles to seed:**

- `platform_admin` (platform-level, no tenant)
- `tenant_owner` (tenant-level, all tenant permissions)
- `manager` (tenant-level, broad permissions)
- `cashier` (tenant-level, POS only)
- `inventory_manager` (tenant-level, inventory focused)
- `accountant` (tenant-level, financial reports)

**Files to create:**

- `app/Concerns/HasPermissions.php` — trait for permission checking
- `app/Http/Middleware/RequirePermission.php`

**Acceptance criteria:**

- Permissions seed without error
- `$user->can('products.create')` works
- Middleware blocks unauthorized access

---

### 1.5 — Tenant Context Middleware

**Objective:** Resolve and inject tenant context on every request.

**Files to create:**

- `app/Http/Middleware/SetTenantContext.php`
- `app/Support/TenantContext.php` — container binding
- Register middleware in `bootstrap/app.php`

**Acceptance criteria:**

- `app(TenantContext::class)->tenant` returns the active tenant
- Unauthenticated requests are not affected
- Wrong/missing tenant redirects to tenant selection

---

### 1.6 — Branches

**Database changes:** `branches` table

**Files to create:**

- `database/migrations/YYYY_create_branches_table.php`
- `app/Models/Branch.php`
- `database/factories/BranchFactory.php`

---

### 1.7 — Base Controllers & Layout

**Objective:** Create base controller with tenant context and update shared Inertia props.

**Files to modify:**

- `app/Http/Middleware/HandleInertiaRequests.php` — share tenant, branch, permissions, branding
- `app/Http/Controllers/Controller.php` — add tenant helper methods

**Files to create:**

- `app/Http/Controllers/Web/DashboardController.php`
- `routes/web.php` — update to use controller

**Shared Inertia props:**

```php
[
    'auth' => ['user' => $user],
    'tenant' => $tenant,
    'branch' => $branch,
    'permissions' => $permissions,
    'branding' => $branding,
    'flash' => ['success' => ..., 'error' => ...],
]
```

---

### 1.8 — Audit Logging

**Database changes:** `audit_logs` table

**Files to create:**

- `database/migrations/YYYY_create_audit_logs_table.php`
- `app/Models/AuditLog.php`
- `app/Services/AuditService.php`
- `app/Concerns/Auditable.php` — trait for auditable models

---

### 1.9 — Platform Administration

**Objective:** Super admin can manage tenants.

**Files to create:**

- `app/Http/Middleware/RequirePlatformAdmin.php`
- `app/Http/Controllers/Web/Admin/TenantController.php`
- `resources/js/pages/admin/tenants/` — index, create, show pages
- `routes/admin.php` (included in `web.php`)

**Acceptance criteria:**

- Platform admin can CRUD tenants
- Non-platform-admin cannot access `/admin/` routes

---

### 1.10 — Tenant Settings & Branding

**Database changes:** `tenant_settings`, `tenant_branding` tables

**Files to create:**

- `database/migrations/YYYY_create_tenant_settings_table.php`
- `database/migrations/YYYY_create_tenant_branding_table.php`
- `app/Models/TenantSetting.php`
- `app/Models/TenantBranding.php`
- `app/Http/Controllers/Web/Settings/TenantSettingsController.php`
- `app/Http/Controllers/Web/Settings/BrandingController.php`
- `resources/js/pages/settings/tenant.tsx`
- `resources/js/pages/settings/branding.tsx`

### 1.11 — Subscription Management (New)

**Objective:** Add subscription plans, trial periods, and billing status to tenants.

**Database changes:** Enhance `tenants` table with `status` field; no new table needed — leverages existing `plan` and `trial_ends_at` columns.

**Files to create:**

- `app/Models/Subscription.php` — thin model wrapping tenant subscription state
- `app/Concerns/HasSubscription.php` — trait with plan status, trial eligibility, and feature-gate methods
- `database/seeders/PlanSeeder.php` — seed default subscription plans (Basic, Pro, Enterprise)

**Acceptance criteria:**

- Tenant `plan` field maps to one of the seeded plan names
- `trial_ends_at` tracks trial expiration
- `status` reflects: `active`, `trialing`, `past_due`, `canceled`, `expired`
- `$tenant->isTrialing()` and `$tenant->isActive()` work via the trait
- Feature gates (e.g., `canManageBranches()`, `canExportReports()`) respect the plan tier

---

## Phase 2 — Product Catalogue

### 2.1 — Product Categories

### 2.2 — Product Brands

### 2.3 — Product Units

### 2.4 — Tax Rates

### 2.5 — Discounts

### 2.6 — Products (with variants)

### 2.7 — Barcodes

Each task follows:

- Migration (ULID PKs, tenant_id, indexes)
- Model (HasUlids, BelongsToTenant, SoftDeletes where applicable)
- Factory
- Policy
- Form Requests (store/update)
- API Resource
- Controller (web)
- Inertia pages (index, create/edit, show)
- Tests (CRUD, tenant isolation, permissions)

---

## Phase 3 — Inventory

### 3.1 — Inventory (current stock)

### 3.2 — Inventory Transactions (ledger)

### 3.3 — Stock Adjustments

### 3.4 — Stock Transfers

### 3.5 — AdjustInventoryAction

### 3.6 — TransferStockAction

**Key constraint:** No direct stock update — all changes via `inventory_transactions` + action classes.

---

## Phase 4 — Parties

### 4.1 — Customer Groups

### 4.2 — Customers

### 4.3 — Suppliers

---

## Phase 5 — Purchasing

### 5.1 — Purchase Orders

### 5.2 — Purchases (with items)

### 5.3 — Purchase Returns

### 5.4 — ReceivePurchaseAction (inventory update on receive)

---

## Phase 6 — POS

### 6.1 — Payment Methods

### 6.2 — Registers

### 6.3 — Register Sessions (open/close)

### 6.4 — Cash Movements (cash in/out)

### 6.5 — OpenRegisterAction

### 6.6 — CloseRegisterAction

### 6.7 — Sales (POS sale flow)

### 6.8 — Sale Items

### 6.9 — Sale Payments

### 6.10 — CreateSaleAction (transactional: sale + items + payments + inventory)

### 6.11 — POS Sale Screen (UI)

### 6.12 — Receipt Generation

**Sale creation must be wrapped in a database transaction.**

---

## Phase 7 — Returns & Refunds

### 7.1 — Sale Returns

### 7.2 — RefundSaleAction (transactional: return + inventory + audit)

### 7.3 — Purchase Returns

---

## Phase 8 — Expenses

### 8.1 — Expense Categories

### 8.2 — Expenses

---

## Phase 9 — Dashboard & Reports

### 9.1 — Dashboard KPIs (today's sales, top products, low stock)

### 9.2 — Sales Report

### 9.3 — Inventory Report

### 9.4 — Customer Report

### 9.5 — Register Session Report

### 9.6 — Expense Report

---

## Phase 10 — PWA

### 10.1 — Install `vite-plugin-pwa`

### 10.2 — Web App Manifest

### 10.3 — Service Worker (Workbox)

### 10.4 — Offline Status Hook

### 10.5 — Network Status UI Component

### 10.6 — IndexedDB Queue Setup

### 10.7 — Offline Sale Creation

### 10.8 — Sync on Reconnect

### 10.9 — Sync Status UI

---

## Phase 11 — API v1

### 11.1 — `routes/api.php` + Sanctum setup

### 11.2 — `AuthController` (login, logout, me)

### 11.3 — API Tenant Context Middleware

### 11.4 — Product API endpoints

### 11.5 — Inventory API endpoints

### 11.6 — Customer API endpoints

### 11.7 — Sales API endpoints (with idempotency)

### 11.8 — Register API endpoints

### 11.9 — Dashboard API endpoint

---

## Phase 12 — Testing

### 12.1 — Tenant isolation tests

### 12.2 — RBAC permission tests

### 12.3 — POS sale workflow tests

### 12.4 — Inventory calculation tests

### 12.5 — Register session tests

### 12.6 — API authentication tests

### 12.7 — Offline sync idempotency tests

### 12.8 — Security boundary tests

---

## Phase 13 — Production Readiness

### 13.1 — Performance audit (N+1 queries, indexes)

### 13.2 — Security audit (CSP, headers, XSS review)

### 13.3 — Demo seeders (platform admin, tenants, sample data)

### 13.4 — `docs/IMPLEMENTATION_REPORT.md`

### 13.5 — Deployment documentation

---

## Task Dependencies

```
Phase 0 → Phase 1 (Users + Tenant + RBAC + Middleware)
    → Phase 2 (Products requires Tenant context)
        → Phase 3 (Inventory requires Products)
            → Phase 5 (Purchasing requires Inventory + Suppliers)
            → Phase 6 (POS requires Inventory + Products + Customers)
                → Phase 7 (Returns require Sales)
    → Phase 4 (Parties can run parallel to products)
    → Phase 8 (Expenses can run parallel)
    → Phase 9 (Dashboard requires sales + inventory data)
Phase 6 → Phase 10 (PWA builds on POS foundation)
Phase 1 → Phase 11 (API requires auth + tenant context)
All phases → Phase 12 (Testing)
All phases → Phase 13 (Production readiness)
Phase 1 + Phase 2 + Phase 3 + Phase 4 + Phase 6 → Phase 16 (tenant storefront)
```

---

## Risk Register

| Risk                                           | Likelihood         | Impact   | Mitigation                                                                        |
| ---------------------------------------------- | ------------------ | -------- | --------------------------------------------------------------------------------- |
| Cross-tenant data leak                         | Low (with scoping) | Critical | Security tests, explicit scoping, code review                                     |
| N+1 query performance                          | Medium             | High     | Eager loading, query monitoring in dev                                            |
| Offline sync conflicts                         | Medium             | High     | Idempotency keys, 48h expiry, conflict UI                                         |
| ULID type mismatch in FK                       | Low                | Medium   | ULID skill enforced, migration review                                             |
| Mobile API breaking changes                    | Low                | Medium   | Versioning, deprecation policy                                                    |
| PWA caching stale data                         | Medium             | Medium   | Cache invalidation strategy, TTLs                                                 |
| Public order lookup exposes customer data      | Medium             | High     | High-entropy scoped tracking token, minimal public response, rate limits          |
| Concurrent storefront checkouts oversell stock | Medium             | High     | Transactional inventory locking, explicit reservation policy, concurrency tests   |
| Payment callback spoofing or replay            | Medium             | Critical | Verify provider signature and transaction server-side; make processing idempotent |
| Analytics collects unnecessary personal data   | Medium             | High     | First-party event allowlist, data minimization, retention and consent policy      |

---

## Phase 16 — Tenant Online Storefront (In Progress)

**Objective:** Let each tenant publish a branded public catalog and accept online orders without weakening tenant isolation or coupling public checkout to staff POS workflows.

**Feasibility:** High as an incremental extension of the existing Laravel monolith: tenant slugs, tenant-scoped products, product variants, branch inventory, branding, payment integrations, and transaction-safe inventory actions already exist. The principal new domain is the online order lifecycle. Effort is **medium to high** because storefront payments, guest access, stock reservation, and fulfillment need rules that POS sales do not currently define.

**Planning estimate:** Approximately **4–6 weeks for one engineer** for a focused v1, assuming a single merchant-payment approach, one fulfillment policy, no custom domains, no carrier integration, and reuse of the current UI stack. Payment-provider onboarding, legal/privacy review, and merchant settlement agreements are outside this estimate and can extend the schedule. Re-estimate after Phase 16.1 decisions.

**Implementation status:** Product publication controls, tenant-scoped public catalogs/product details, a per-tenant storefront enable switch, tenant bank-transfer instructions, and per-branch local-delivery fees are implemented. Checkout, order tracking, analytics, staff order management, inventory reservation, and payment confirmation remain unimplemented. Offline order placement is the selected v1 payment model; no payment-provider integration is enabled.

### 16.1 — Decide storefront, payment, and fulfillment rules

- [x] Use manual/offline payment in v1: cash on pickup/delivery or bank transfer, with staff confirming receipt. Do not assume the subscription Paystack secret can accept tenant sales.
- [x] Support pickup and local delivery. Start reservations at the tenant's main branch; staff may reassign only when the destination branch has enough stock.
- [x] Allow guest checkout with optional accounts; require name and phone, allow email, and require a delivery address for local delivery.
- [x] Define customer cancellation and manual refund handling for orders whose offline payment has already been confirmed.
- [x] Reuse existing product tax rates and tenant tax-inclusive settings; configure local delivery fees per branch.
- [x] Use `/shop/{tenant slug}` and defer custom domains until domain verification and tenant-routing operations are designed.
- [x] Define storefront publication and availability separately from internal product activity and POS availability.

**Gate:** Do not add automated online payment until merchant-of-record, settlement, refund ownership, and webhook ownership are agreed. Customer orders may use the selected manual/offline methods.

### 16.2 — Store configuration and product publication

- [x] Add tenant-owned storefront enabled state, bank-transfer instructions, and per-branch local-delivery fee controls; reuse tenant branding for store identity.
- [x] Add public description/contact details, fulfillment settings, and any store-specific policy text.
- [x] Add an explicit product publication control and keep existing `is_active` semantics intact for POS and inventory. Variant publication remains a follow-up.
- [x] Add tenant settings UI gated by the existing `settings.view` and `settings.update` permissions; derive the tenant from authenticated context, not a submitted tenant ID.
- [x] Validate published products have a usable price, public content, and valid active variants before they can be listed.

### 16.3 — Public store resolution and catalog

- [x] Resolve a tenant from its unique slug and require the tenant to be active with its storefront enabled.
- [x] Keep guest storefront routes separate from authenticated staff routes and POS routes.
- [x] Build public catalog and product-detail pages with product images, active variants, and tenant currency display. Category filtering, search, and stock messaging remain follow-ups.
- [x] Query only the resolved tenant's active, published products and explicitly serialize public fields; never expose costs, internal notes, tenant contact/billing details, or other tenants' records.
- [x] Deliver public images from the public storage disk. Storefront caching remains a follow-up; no private checkout/customer responses are cached.

### 16.4 — Cart and online order lifecycle

- [x] Implement a tenant-bound guest cart with server-side product/variant validation and server-calculated prices and tax. Discounts and shipping remain out of scope until defined.
- [x] Add a dedicated online order aggregate and order-line snapshots (product name/SKU/variant, unit price, quantity, tax); preserve existing POS sales behavior.
- [x] Define explicit order and payment states, including pending, accepted, ready, completed, cancelled, unpaid, paid, and refunded, with guarded transitions.
- [x] Use unique idempotency keys for checkout. Repeated submits must not create duplicate orders or inventory reservations. Webhook processing is not in this offline-payment release.
- [x] Persist only necessary customer data, protect it with tenant scoping/authorization, and define retention/deletion rules.

### 16.5 — Inventory, payment, and fulfillment

- [x] Choose the active main fulfillment branch deterministically and ensure it belongs to the order's tenant; support stock-checked staff reassignment.
- [x] Reserve or decrement inventory within a database transaction with row locking and sufficient-stock checks. Cancellation releases holds; payment consumes them. Automatic expiry remains a follow-up.
- [x] Never accept price, tax, stock, payment status, or tenant ownership from the browser as authoritative.
- [x] Initialize provider payments server-side; verify webhook signature and then independently verify provider status, reference, amount, and currency before marking an order paid or fulfilling it.
- [x] Store provider references and event identifiers with uniqueness constraints; handle duplicated, delayed, out-of-order, and invalid events safely.
- [x] Keep merchant order payment configuration separate from platform subscription billing credentials and records.
- [x] Provide tenant staff screens for order review, fulfillment status, cancellation, refunds, and audit history. Customer notifications remain a follow-up.

### 16.6 — Customer order status and delivery tracking

- [x] Provide an order confirmation and a customer-accessible order status page; do not expose order details through sequential IDs or guessable references alone.
- [x] For guest access, issue a cryptographically random, order-scoped tracking credential and persist only its digest. Public lookups are rate-limited; revocation/expiry remains a follow-up.
- [x] Return the minimum information required to identify the order and show status. Exclude full address, payment credentials, internal notes, and unrelated customer/order data from public tracking responses.
- [x] Record status changes as an append-only order timeline with actor, timestamp, and tenant context; keep payment status separate.
- [x] In v1, support manual fulfillment updates and tracking number/carrier fields only if shipping is selected. Defer carrier API integrations until a specific provider is chosen.
- [x] Notify customers about confirmation and important status changes without putting bearer tracking credentials or sensitive personal data in logs.

### 16.7 — Storefront analytics and progress tracking

- [x] Define a small first-party event allowlist: product viewed, add/remove cart item, checkout started, and order paid. Treat purchase events as server-confirmed order/payment events, not client claims.
- [x] Scope every event to a tenant/store and use a random pseudonymous session identifier where needed; do not collect payment data, email, phone, full IP, or free-form form contents for analytics.
- [x] Document consent behavior, purpose, retention/deletion period, and tenant visibility before analytics collection is enabled; avoid third-party trackers in v1.
- [x] Add tenant-scoped reports for funnel counts and product performance, with aggregation and authorization that prevent cross-tenant disclosure.
- [ ] Track engineering progress against this phase's checkboxes and acceptance gates; keep implementation issues/tasks linked to these subsections, record blockers/decisions, and mark work complete only after tests pass.

### 16.8 — Security, tests, and rollout gates

- [x] Add feature tests for storefront enable/disable, tenant settings authorization and payload isolation, inactive tenants, unpublished products, cross-tenant product IDs, and private-field exposure.
- [x] Add feature tests for cart tampering, guarded order status/payment transitions, stock reservation protection, branch reassignment, and idempotent checkout. Add true concurrent checkout testing before production rollout.
- [x] Add payment tests for invalid signatures, mismatched amount/currency/reference, duplicate and out-of-order webhooks, provider failures, and retry behavior.
- [x] Add tracking tests for token entropy, valid/invalid guest credentials, throttling, absence of sensitive fields, analytics opt-in behavior, tenant-scoped reporting, and analytics retention.
- [x] Rate-limit storefront cart writes, checkout, and guest status lookups by tenant and client; preserve CSRF for stateful browser writes and validate accepted input fields. Add a search limiter if public search is introduced.
- [x] Audit logs for tenant store setting/publication changes, order state transitions, refunds, and privileged tracking access. Redact secrets and personal data from logs/analytics.
- [ ] Review accessibility, mobile layout, image upload/storage policy, CSP, cache headers, retention/deletion, and incident handling before production.
- [ ] Pilot with selected tenants, reconcile a complete order/payment/stock/refund path, then enable storefronts in controlled rollout.

**Acceptance criteria**

- An unauthenticated customer can browse only an enabled tenant's published products and place an order without accessing staff/POS endpoints.
- Tenant A cannot view or mutate Tenant B's catalog, cart, orders, stock, analytics, or order status, including by changing slugs, IDs, headers, or request payloads.
- Server-calculated order totals match immutable order snapshots; duplicate checkout/payment events do not duplicate value or stock movement.
- No order is marked paid or fulfilled based only on a browser redirect or client event.
- Customers can securely view their own order status, and tenant staff can audit and update fulfillment through authorized workflows.
- Funnel reporting contains only documented, tenant-scoped, privacy-minimized events and does not count unverified payment as a purchase.

**Research references**

- [Medusa sales channels](https://docs.medusajs.com/resources/commerce-modules/sales-channel) — product availability, cart/order scoping, and inventory association by channel.
- [Vendure channels](https://docs.vendure.io/current/framework/reference/typescript-api/entities/channel) — channel-specific products, currency/tax, stock locations, payment methods, customers, and orders.
- [Shopify order status page](https://shopify.dev/docs/api/customer-account-ui-extensions/latest/targets/order-status) — order-level status and delivery tracking as a customer-facing surface.
- [Paystack accepting payments](https://paystack.com/docs/payments/accept-payments/) and [webhooks](https://paystack.com/docs/payments/webhooks/) — server-side payment initiation, signature verification, retries, and trusted payment confirmation.
