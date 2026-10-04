# Multi-Tenancy Design

## Strategy: Shared Database, Shared Tables, tenant_id Scoping

### Decision Summary

TickSales uses a **single database with a `tenant_id` column on every tenant-scoped table**. This is the appropriate strategy for an SMB SaaS POS platform because:

| Factor | Shared DB/Tables | Separate DBs |
|--------|-----------------|--------------|
| Operational complexity | Low | High |
| Backup/restore | Simple (one DB) | One backup per tenant |
| Cross-tenant reporting (platform admin) | Easy | Complex |
| Tenant isolation | Via application + scopes | Via infrastructure |
| Scale to many tenants | Excellent | DB provisioning overhead |
| Data leakage risk | Mitigated by scopes + tests | Mitigated by isolation |
| Cost | Low | Higher (multiple DB connections) |

The risk of cross-tenant data leakage is mitigated through:
1. Eloquent global scopes applied automatically
2. Dedicated `TenantContext` middleware
3. Policies enforcing tenant membership
4. Explicit security tests that verify Tenant A cannot read Tenant B data
5. Never trusting `tenant_id` from request input

---

## Tenant Model

```
Platform
└── Tenant (Business)
        ├── Users (via tenant_users pivot)
        ├── Roles (tenant-scoped)
        ├── Branches
        │     ├── Users (branch assignments)
        │     ├── Registers
        │     └── Inventory
        ├── Products
        ├── Customers
        ├── Suppliers
        ├── Settings
        └── Branding
```

---

## Core Tables

### `tenants`
```sql
id            char(26) PK (ULID)
name          string
slug          string UNIQUE
email         string
phone         string nullable
address       text nullable
country       string nullable
currency      string default 'USD'
timezone      string default 'UTC'
locale        string default 'en'
is_active     boolean default true
trial_ends_at timestamp nullable
plan          string nullable
created_at    timestamp
updated_at    timestamp
deleted_at    timestamp nullable (soft delete)
```

### `tenant_users`
Junction table linking users to tenants with role and branch context.
```sql
id         char(26) PK (ULID)
tenant_id  char(26) FK → tenants
user_id    FK → users (integer — framework table exempt from ULID FK)
role_id    char(26) FK → roles nullable
is_owner   boolean default false
is_active  boolean default true
joined_at  timestamp
created_at timestamp
updated_at timestamp
UNIQUE(tenant_id, user_id)
```

> **Note:** `user_id` references the existing `users` table which uses integer PK (framework-managed, exempt from ULID skill Rule 4). Use `foreignId('user_id')` for this specific FK only.

### `branches`
```sql
id          char(26) PK (ULID)
tenant_id   char(26) FK → tenants
name        string
code        string nullable
address     text nullable
phone       string nullable
email       string nullable
is_main     boolean default false
is_active   boolean default true
created_at  timestamp
updated_at  timestamp
deleted_at  timestamp nullable
INDEX(tenant_id)
```

---

## Tenant Context Resolution

### Middleware: `SetTenantContext`

Applied to all authenticated web and API routes. Resolves the active tenant from the authenticated user's `tenant_users` membership.

```php
// Resolution strategy (web — session-based tenant selection):
1. Check session for 'active_tenant_id'
2. Verify user has membership in that tenant
3. If not set or invalid → resolve user's first active tenant
4. Inject resolved Tenant into request and app container

// Resolution strategy (API — from Sanctum token):
1. Token has tenant_id embedded in abilities OR
2. Tenant ID passed as X-Tenant-ID header (validated against membership)
```

### Middleware: `SetBranchContext`

Applied to routes requiring branch-level context (POS sales, register sessions).

```php
1. Check session/request for 'active_branch_id'
2. Verify branch belongs to active tenant
3. Verify user has access to that branch
4. Inject resolved Branch into request and app container
```

---

## Eloquent Scoping Strategy

### Approach: Manual Scoping (No Global Scope Package)

Every model that is tenant-scoped will include a `BelongsToTenant` trait that:
1. Automatically injects `tenant_id` on model creation
2. Provides a `forTenant(Tenant $tenant)` scope helper

Controllers and actions always scope queries explicitly:

```php
// ✅ Correct — always scope to the current tenant
$products = Product::query()
    ->forCurrentTenant()
    ->paginate();

// ✅ Also correct — explicit tenant_id
$products = Product::where('tenant_id', $tenant->id)->paginate();

// ❌ Never — unbounded query on a tenant-scoped table
$products = Product::all();
```

**Why not global scope?** Global scopes applied silently can be accidentally removed with `withoutGlobalScopes()` or forgotten in new contexts (jobs, commands, events). Explicit scoping makes tenant isolation visible in code and easier to audit.

---

## Platform Administration

Platform (super) admins are identified by a `is_platform_admin` flag on the `users` table. They do not belong to any specific tenant — they manage all tenants from a dedicated platform administration panel.

```
/admin/               ← Platform administration (super admin only)
/dashboard/           ← Tenant dashboard (tenant users)
```

Platform admin routes are protected by the `platform_admin` middleware which checks `$user->is_platform_admin`.

---

## Tenant Isolation Verification Tests

The following cross-tenant isolation tests will be maintained:

```php
test('tenant A user cannot read tenant B products')
test('tenant A user cannot create sales for tenant B')
test('tenant A user cannot access tenant B customers')
test('tenant A API token cannot query tenant B inventory')
test('platform admin can read any tenant data')
test('platform admin actions are audited')
```

---

## Tenant Switching (Multi-tenant Users)

A user may belong to multiple tenants (e.g., an accountant managing two businesses). The application will provide a tenant-switcher UI component.

```
User → belongs to → [Tenant A, Tenant B]
                         ↓ switch
                    Active Tenant = Tenant B
                    (stored in session)
```

On tenant switch:
1. Validate user has membership in target tenant
2. Update session `active_tenant_id`
3. Clear branch context
4. Redirect to tenant dashboard

---

## Data Ownership Map

| Table Group | Scope | Notes |
|-------------|-------|-------|
| users | Platform | Shared; `tenant_users` provides tenant membership |
| tenants | Platform | Managed by platform admin |
| roles | Tenant | Each tenant defines their own roles |
| permissions | Platform | Defined by platform; tenants assign to roles |
| branches | Tenant | |
| products | Tenant | Shared across branches by default |
| inventory | Tenant + Branch | Stock quantities are branch-specific |
| customers | Tenant | |
| suppliers | Tenant | |
| sales | Tenant + Branch | |
| purchases | Tenant + Branch | |
| expenses | Tenant + Branch | |
| registers | Tenant + Branch | |
| audit_logs | Tenant + Platform | Platform admin sees all; tenant admin sees own |
| tenant_settings | Tenant | |
| tenant_branding | Tenant | |
