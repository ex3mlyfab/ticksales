# API Architecture

## Overview

The TickSales mobile API provides a versioned RESTful JSON API for iOS and Android applications. It is **entirely separate** from the Inertia web routes — no internal Inertia controllers are exposed as API endpoints.

---

## Base URL

```
/api/v1/
```

---

## Authentication

### Mechanism: Laravel Sanctum (Personal Access Tokens)

Mobile clients authenticate with email/password and receive a Sanctum personal access token. All subsequent requests use this token as a Bearer token.

```http
Authorization: Bearer <token>
```

### Token Lifecycle

| Event | Action |
|-------|--------|
| Login | Issue new token with tenant/branch abilities |
| Logout | Revoke current token |
| Password change | Revoke all user tokens |
| Admin deactivation | Revoke all user tokens for tenant |
| Token expiry | 30 days (configurable) |

### Tenant Context in API

Tokens carry Sanctum abilities encoding the tenant and branch:

```php
$token = $user->createToken(
    name: "Mobile:{$deviceName}",
    abilities: [
        "tenant:{$tenant->id}",
        "branch:{$branch->id}",
    ],
    expiresAt: now()->addDays(30),
);
```

Alternatively (for users who can switch branches), the active branch is sent as a header:

```http
X-Branch-ID: 01ARYZ6S41TSV4RRFFQ69G5FAV
```

This header is validated server-side against the user's branch memberships.

---

## Response Format

### Success Response

```json
{
    "success": true,
    "message": "Sale created successfully.",
    "data": { },
    "meta": { }
}
```

### Success with Pagination

```json
{
    "success": true,
    "message": null,
    "data": [ ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "last_page": 8,
        "from": 1,
        "to": 20
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

### Validation Error Response

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "email": ["The email field is required."],
        "amount": ["The amount must be greater than 0."]
    }
}
```

### Authentication Error Response

```http
401 Unauthorized
```
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### Authorization Error Response

```http
403 Forbidden
```
```json
{
    "success": false,
    "message": "You do not have permission to perform this action."
}
```

### Not Found Response

```http
404 Not Found
```
```json
{
    "success": false,
    "message": "Resource not found."
}
```

### Server Error Response

```http
500 Internal Server Error
```
```json
{
    "success": false,
    "message": "An unexpected error occurred."
}
```

---

## API Endpoint Catalogue

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/auth/login` | Login, receive token | None |
| POST | `/api/v1/auth/logout` | Revoke current token | Bearer |
| GET | `/api/v1/auth/me` | Current user + tenant context | Bearer |
| POST | `/api/v1/auth/refresh` | Exchange near-expiry token | Bearer |
| GET | `/api/v1/auth/permissions` | User's permission list | Bearer |

#### Login Request
```json
{
    "email": "cashier@shop.com",
    "password": "secret",
    "device_name": "iPhone 15 - Cashier 1",
    "tenant_slug": "main-street-store"
}
```

#### Login Response
```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "token": "...",
        "token_type": "Bearer",
        "expires_at": "2026-11-03T10:00:00Z",
        "user": { },
        "tenant": { },
        "branch": { },
        "permissions": [ ]
    }
}
```

---

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dashboard` | Dashboard KPIs for current tenant/branch |

---

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | List products (paginated, filterable) |
| GET | `/api/v1/products/{product}` | Single product with variants |
| GET | `/api/v1/products/search` | Search by name/barcode/SKU |

#### Query Parameters (products list)
```
?page=1
?per_page=20
?search=coffee
?category_id=...
?is_active=1
?sort=name&direction=asc
```

---

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/product-categories` | All categories (flat list) |

---

### Inventory

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/inventory` | Current stock levels for branch |
| GET | `/api/v1/inventory/{product}` | Stock for specific product |

---

### Customers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/customers` | List customers |
| POST | `/api/v1/customers` | Create customer |
| GET | `/api/v1/customers/{customer}` | Single customer |
| PUT | `/api/v1/customers/{customer}` | Update customer |

---

### Sales

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sales` | List sales (filterable by date) |
| POST | `/api/v1/sales` | Create sale |
| GET | `/api/v1/sales/{sale}` | Single sale with items + payments |
| POST | `/api/v1/sales/{sale}/return` | Create sale return |

#### Create Sale Request
```json
{
    "idempotency_key": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": null,
    "register_session_id": "...",
    "items": [
        {
            "product_id": "...",
            "variant_id": null,
            "quantity": 2,
            "unit_price": 150.00,
            "discount_amount": 0
        }
    ],
    "payments": [
        {
            "payment_method_id": "...",
            "amount": 300.00
        }
    ],
    "discount_amount": 0,
    "note": null,
    "offline_created_at": null
}
```

---

### Registers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/registers` | Available registers for branch |
| GET | `/api/v1/registers/{register}/session` | Current open session |
| POST | `/api/v1/registers/{register}/open` | Open register session |
| POST | `/api/v1/registers/{register}/close` | Close register session |
| POST | `/api/v1/registers/{register}/cash-movement` | Record cash in/out |

---

### Payment Methods

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payment-methods` | Active payment methods |

---

### Reports (Basic)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/reports/sales-summary` | Sales summary for date range |
| GET | `/api/v1/reports/register-session/{session}` | Register session summary |

---

## File Structure

```
app/
└── Http/
    └── Controllers/
        └── Api/
            └── V1/
                ├── AuthController.php
                ├── DashboardController.php
                ├── ProductController.php
                ├── CategoryController.php
                ├── InventoryController.php
                ├── CustomerController.php
                ├── SaleController.php
                ├── SaleReturnController.php
                ├── RegisterController.php
                ├── PaymentMethodController.php
                └── ReportController.php
    └── Resources/
        └── Api/
            └── V1/
                ├── UserResource.php
                ├── TenantResource.php
                ├── ProductResource.php
                ├── SaleResource.php
                ├── SaleItemResource.php
                └── ...
```

---

## Route Registration

```php
// routes/api.php
Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('auth/login', [AuthController::class, 'login']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('auth/permissions', [AuthController::class, 'permissions']);

        // Tenant-context routes (require SetApiTenantContext middleware)
        Route::middleware('api.tenant')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::apiResource('products', ProductController::class)->only(['index', 'show']);
            Route::get('products/search', [ProductController::class, 'search']);
            Route::apiResource('customers', CustomerController::class);
            Route::apiResource('sales', SaleController::class);
            Route::post('sales/{sale}/return', [SaleReturnController::class, 'store']);
            // ... etc
        });
    });
});
```

---

## API Conventions

### Pagination
All list endpoints are paginated. Default: 20 items/page. Max: 100.

### Filtering
Use query parameters. Nested filters: `?filter[status]=completed`

### Sorting
`?sort=created_at&direction=desc`

### Date Formats
All timestamps in ISO 8601 UTC: `2026-10-04T00:00:00Z`

### Currency
All monetary values as decimals with 4 decimal places internally; formatted with 2 dp in responses.

### Idempotency
POST endpoints for sales, payments, and returns accept `idempotency_key` (UUID v4). Duplicate submissions within 24 hours return the original response.

### Versioning
Major breaking changes → new version prefix (`/api/v2/`). Non-breaking additions are backwards compatible within the version.
