# TickSales Code Review — Fixing Plan & Tracker

Source review: code review findings (2026-11-01). This document is the single tracker for the
remediation effort. Each row records the finding, the fix, its verification, and its status.

Legend: ✅ Done · 🔄 In progress · ⬜ Not started · ⛔ Blocked / deferred

## Security monitoring

Every security-relevant fix ships with a **regression test** that asserts the observable behaviour
(pass *and* fail paths), so a regression breaks the suite. These tests are the monitor:

- `tests/Feature/Security/PermissionEnforcementTest.php` — six cases: a tenant owner is allowed
  (web + API), a member without the permission is denied (403) on a gated route, a member with an
  unrelated role is denied the route it lacks but allowed the one it holds, and a user who is owner
  in tenant A but a restricted member in tenant B is denied tenant-A-level permissions while
  operating in tenant B (cross-tenant permission leak).
- Inventory integrity (stock decrement, atomic posting, no negative stock, no double receipt) is
  asserted by the existing feature suite (`tests/Feature/PosSaleTest.php`, stock transfer/adjustment
  and purchase tests) — the behaviour is exercised by the same tests that already cover those paths.
- Tenant context (`tests/Feature/DashboardTenantRoutingTest.php`) — a user landing on
  `/t/{tenant}/dashboard` for a tenant they are **not** an active member of is refused (404, no
  existence leak), guests are redirected to login, and a successful landing pins that tenant as the
  session's active tenant for subsequent requests.
- Cross-tenant data isolation via validation (`tests/Feature/Security/TenantScopedValidationTest.php`)
  — a product cannot be created with a category, an expense with a branch, or a category with a
  parent owned by **another** tenant, while the same-tenant case still succeeds. This pins the
  `exists:` rules to the active tenant (row P-13): before the fix, the cross-tenant product case
  was accepted.
- Query-cost regression (`tests/Feature/SaleReturnTest.php`, "refunding a multi-line sale does not
  lazy load per line") — asserts a multi-line partial refund stays under a bounded query count
  (37), which the old per-line `$item->sale->returns` lazy load violated (~50). Keeps the
  `RefundSaleAction` N+1 fixed (row P-12).
- Admin tenant administration is audited (`tests/Feature/PlatformAdminTest.php`) — creating,
  updating, and deleting a tenant each writes a `tenant.created` / `tenant.updated` /
  `tenant.deleted` row to `audit_logs` attributed to the acting platform admin and the affected
  tenant (row P-18).

CI runs `composer ci:check` on push/PR, which executes `@test` → `pint --test`, `phpstan analyse`,
and `php artisan test`. PHPStan is now green via a generated baseline
(`phpstan-baseline.neon`, 721 pre-existing type-analysis findings); any *new* PHPStan error fails
CI, so the static gate stays honest while the bulk legacy debt is tracked (row P-14). The new
security tests run inside `php artisan test`.

## Findings → fixes

| ID | Severity | Finding | Fix | Verification | Status |
|----|----------|---------|-----|--------------|--------|
| P-01 | High | No effective authorization: `permission` middleware unused, policies never invoked, requests `authorize()=true` | Add `permission:<module>.<action>` middleware to every business route; extend `PermissionSeeder` with missing module permissions; align test helper with an owner role | `PermissionEnforcementTest` (denied 403 / allowed 200) | ✅ |
| P-02 | High | Permissions leak across tenants (`HasPermissions::permissions()` ignores active tenant / `is_active`) | Scope `permissions()` to the active tenant + active membership; memoize per request; owner/platform-admin bypass | `PermissionEnforcementTest` — cross-tenant case | ✅ |
| P-03 | High | Web POS sale: unscoped `exists:` validation + `CreateSaleAction` doesn't verify product/customer/session/branch belong to tenant | Scope validation to tenant; verify ownership in the action | `PermissionEnforcementTest` + POS tests | ✅ |
| P-04 | High | API-created sales never decrement inventory / log transaction | Route API sale through the shared inventory ledger (`AdjustInventoryAction::handle`) | `PosSaleTest` (inventory decrement + ledger) | ✅ |
| P-05 | High | ~~Purchase-order item `line_total` double-counts tax~~ — **verified NOT a defect**: `line_total = (subtotal − discount) + tax` is single-tax and matches the order total | None required (review false positive) | Manual: `line_total = taxable × (1 + rate)` ≡ order total | ✅ (N/A) |
| P-06 | High | Stock transfer/adjustment `post` not atomic; no stock-sufficiency check (can go negative) | Wrap status + all items in one transaction; check sufficient stock on decrement | Stock transfer/adjustment tests | ✅ |
| P-07 | High | Duplicate stock receipt: `receiveExisting` re-adds stock; PO can be re-received | Guard on prior status in `receiveExisting`; reject a `purchase_order_id` already received | Purchase tests | ✅ |
| P-08 | Med | `reference_no` generated from `count()` — race duplicates | Collision-resistant `YmdHis` + random suffix | Existing sale tests | ✅ |
| P-09 | Med | `sales.idempotency_key` globally unique but checked per-tenant | Align web validation to `uuid`; tenant-scoped lookup already in `CreateSaleAction` | Sale tests | ✅ |
| P-10 | Med | Draft stock adjustment records `quantity_before=0`, `quantity_after=delta` | Read actual inventory for before/after | Stock adjustment tests | ✅ |
| P-11 | Med | Register-close expected balance ignores non-cash payments + change handed back | Cash-only payments, subtract change given | `PosSaleTest` close-register case | ✅ |
| P-12 | Med | N+1 / repeated queries: `permissions()` per request; `RefundSaleAction` lazy loads | Memoized `permissions()` per tenant per request (separate `$ownershipCache`); refactored `RefundSaleAction` to precompute returned quantities (was lazy-loading `$item->sale->returns` per line); eager-loaded `sale.customer` + `items.product` on sale-return index; `withCount('sessions')` on register index | `SaleReturnTest` query-count monitor (37 queries vs ~50 with the old per-line load) | ✅ |
| P-13 | Med | Unscoped `exists:` validation across form requests | New `ScopesValidationToTenant` trait; applied to all 17 form requests + 2 controllers (`RegisterSessionController`, `SaleReturnController`) so every tenant-owned FK validates inside the active tenant | `TenantScopedValidationTest` (cross-tenant category/branch/parent rejected; same-tenant allowed) | ✅ |
| P-14 | Med | PHPStan red (721 findings; bulk missing generics on relations) | Generated `phpstan-baseline.neon` (ignores legacy); new errors fail CI | `phpstan analyse` → `[OK] No errors` | ✅ (baselined) |
| P-15 | Low | Policies never wired; `?string $tenantId` unused | Resolved by P-01: route-level middleware is the enforcement; no controller uses `authorize()`/`Gate` | `PermissionEnforcementTest` | ✅ |
| P-16 | Low | `SetTenantContext` runs twice (global web append + route middleware) | Verified idempotent (harmless); left as-is | — | ✅ (N/A) |
| P-17 | Low | `status 'partial'` in API validation inconsistent with `partially_refunded` | Aligned enum value to `partially_refunded` | API sale tests | ✅ |
| P-18 | Low | No audit logging for admin tenant CRUD | `AuditService::log()` now accepts an optional tenant override; `TenantController` logs `tenant.created` / `tenant.updated` / `tenant.deleted` | `PlatformAdminTest` (create + update/delete audit entries) | ✅ |
| P-19 | Med | Tenant context was implicit: a single `/dashboard` resolved from session/input/header with no URL identity, so tenants had no landing URL of their own and a stale branch could silently blank the page | Added `GET /t/{tenant}/dashboard` (per-tenant landing that sets the active tenant); `SetTenantContext` now resolves a membership-scoped `{tenant}` route param before session/input/header; `HandleInertiaRequests` shares the user's `tenants` list; `TenantSwitcher` in the sidebar header visits the per-tenant dashboard. `/dashboard` still renders the active tenant (backward compatible) | `DashboardTenantRoutingTest` (member 200, sticky active tenant, non-member 404, guest → login) | ✅ |

## Runbook

```bash
# after each fix cluster
php artisan test --compact                # feature suite (must stay green)
vendor/bin/pint --test                    # style gate
vendor/bin/phpstan analyse                # static gate (see P-14)
```

## Constraints & decisions

- **Test helper** `attachUserToTenant` previously attached **no role**. It now attaches a
  `tenant_owner` role that holds all permissions **and** marks the membership `is_owner`, matching
  `RoleSeeder`'s intent. Dedicated permission-denial tests create a member *without* that role and
  without `is_owner`.
- **Owners bypass RBAC.** A membership flagged `is_owner` (and `is_platform_admin` users) short-circuit
  `hasPermissionTo()` to `true`, so owners always act on their tenant regardless of how many
  permissions are seeded. This matches the domain intent and keeps owner-based tests green.
- **Authorization model**: server-side enforcement lives in route-level `permission:` middleware
  (already registered in `bootstrap/app.php`), not client-side props. The shared `permissions` prop
  remains for UI visibility only.
- Tenant *data* isolation (`where('tenant_id', ...)` + `abort_unless(tenant_id === tenant->id, 403)`)
  is already strong and tested — it is preserved, not replaced.
