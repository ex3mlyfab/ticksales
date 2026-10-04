# Security Design

## Threat Model Summary

TickSales is a multi-tenant SaaS application handling financial transactions, inventory, and business data. The primary threats are:

1. **Cross-tenant data leakage** — User from Tenant A reads Tenant B data
2. **Privilege escalation** — Cashier performs admin-only operations
3. **IDOR (Insecure Direct Object Reference)** — Accessing resources by ID without ownership check
4. **Mass assignment** — Unintended field assignment via Eloquent
5. **API key theft** — Mobile Sanctum token compromise
6. **Injection attacks** — SQL, XSS, command injection
7. **Session hijacking** — Stale/stolen session
8. **Offline transaction fraud** — Forged offline sales during sync

---

## 1. Tenant Isolation

### Rule: Never trust `tenant_id` from request input

The active tenant is always resolved from the authenticated user's session/token — never from a request parameter.

```php
// ❌ NEVER do this
$tenantId = $request->input('tenant_id');

// ✅ Always resolve from context
$tenantId = app(TenantContext::class)->tenant->id;
// or
$tenantId = $request->user()->activeTenant()->id;
```

### Rule: Every tenant-scoped query must be explicitly scoped

```php
// ✅ Correct
Product::where('tenant_id', $tenant->id)->findOrFail($id);

// ❌ Wrong — no tenant scope
Product::findOrFail($id);
```

### Rule: Policies must verify tenant membership

```php
class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->belongsToTenant($product->tenant_id)
            && $user->can('products.view');
    }
}
```

---

## 2. Authorization (RBAC)

### Server-Side First

All authorization is enforced server-side via Laravel Policies. Frontend permission checks control **UI visibility only** and must never be relied upon for security.

```php
// In controller — always authorize
$this->authorize('create', Product::class);

// In Inertia — permissions shared for UI rendering only
'permissions' => $user->permissionList(),
```

### Policy Registration

All models have corresponding Policies registered in `AuthServiceProvider`.

---

## 3. CSRF Protection

All state-changing web routes are protected by Laravel's built-in CSRF middleware. Inertia handles CSRF tokens automatically through the `X-XSRF-TOKEN` header on all XHR requests.

API routes (`/api/v1/`) use Sanctum token authentication and are exempt from CSRF (stateless).

---

## 4. XSS Prevention

- React's JSX automatically escapes all interpolated values
- No use of `dangerouslySetInnerHTML` unless content is explicitly sanitized
- Content Security Policy (CSP) headers configured for production
- Tenant-provided content (branding, business name) displayed through escaped React rendering

---

## 5. SQL Injection Prevention

- Eloquent ORM and query builder use parameterized queries exclusively
- No raw SQL string concatenation with user input
- PHPStan / Larastan static analysis catches potential type errors
- Where raw queries are necessary, use `DB::select()` with bindings

---

## 6. Mass Assignment Protection

All models use either `#[Fillable]` attribute or `$guarded = ['*']` with explicit fill. No model uses `$guarded = []`.

```php
#[Fillable(['name', 'price', 'tenant_id'])]
class Product extends Model { }
```

---

## 7. API Security (Sanctum)

### Token Scoping

Mobile API tokens are issued with ability scopes tied to the tenant and branch context at time of login:

```php
$token = $user->createToken(
    name: "Mobile:{$device}",
    abilities: ["tenant:{$tenant->id}", "branch:{$branch->id}"],
    expiresAt: now()->addDays(30),
);
```

### Token Validation Middleware

Every API request validates that the token's tenant/branch ability matches the requested resource.

### Token Revocation

Tokens can be revoked:
- By the user (logout from device)
- By tenant admin (revoke user access)
- By platform admin (emergency)
- Automatically on password change

---

## 8. Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Login | 5 / minute / IP + email |
| Two-factor | 5 / minute / session |
| Passkeys | 10 / minute |
| Password reset | 5 / minute |
| API (general) | 60 / minute / token |
| API (auth) | 10 / minute / IP |

---

## 9. Session Security

```php
// config/session.php
'secure'   => true,          // HTTPS-only in production
'http_only' => true,         // No JS access
'same_site' => 'lax',       // CSRF protection
'encrypt'  => false,         // Session data encrypted at rest via DB
'lifetime' => 120,           // 2 hour default
```

---

## 10. Password Security

In production, passwords must:
- Be at least 12 characters
- Contain mixed case, letters, numbers, and symbols
- Not be in the HaveIBeenPwned database (Fortify uncompromised() check)

---

## 11. File Upload Security

- Only allow specific MIME types (images, PDFs)
- Validate MIME from file content (not just extension)
- Store uploads outside public root
- Generate signed URLs for access
- Scan for malware in production (configurable)
- Maximum file size enforced

---

## 12. Offline Transaction Security

Offline sales created in the PWA carry:
- A client-generated UUID (idempotency key)
- A signed timestamp
- Tenant/branch context from the cached session

On sync:
- Server validates the idempotency key has not been used
- Server validates the user had a valid active register session at the time
- Server rejects sync attempts more than 24 hours old
- Server validates inventory was sufficient at the time (best-effort)

---

## 13. Audit Logging

All security-critical events are logged to `audit_logs`:

```
user.login
user.logout
user.login_failed
user.password_changed
user.2fa_enabled
user.2fa_disabled
tenant.created
tenant.deactivated
role.assigned
role.revoked
permission.changed
product.price_changed
sale.created
sale.refunded
register.opened
register.closed
stock.adjusted
settings.changed
api_token.created
api_token.revoked
```

Audit logs include:
- Actor user ID
- Tenant ID
- Branch ID (if applicable)
- IP address
- User agent
- Before/after values (JSON)
- Timestamp

---

## 14. IDOR Prevention

All resource access validates ownership through:
1. Policy authorization (`$this->authorize(...)`)
2. Tenant-scoped query (ensures the record belongs to the tenant)
3. Route model binding only resolves within the tenant scope

```php
// Route model binding scoped to tenant
Route::get('products/{product}', [ProductController::class, 'show'])
    ->scopeBindings();

// Policy ensures tenant membership
public function show(User $user, Product $product): bool
{
    return $user->tenant_id === $product->tenant_id;
}
```

---

## 15. Headers (Production)

```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: [configured per environment]
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

---

## Security Testing Requirements

The test suite must include:

```
test('tenant A cannot read tenant B products via web')
test('tenant A cannot read tenant B products via API')
test('unauthorized user cannot access admin routes')
test('cashier cannot perform manager-only operations')
test('API token is validated for correct tenant')
test('offline sync rejects duplicate idempotency keys')
test('offline sync rejects expired transactions')
test('password change revokes all tokens')
test('platform admin cannot be impersonated by tenant admin')
```
