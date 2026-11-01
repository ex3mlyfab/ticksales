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

Mobile and external clients authenticate with email/password at `POST /api/v1/auth/login` and receive a Sanctum personal access token. All subsequent requests use this token as a Bearer token.

```http
Authorization: Bearer <token>
```

> The API routes use `auth:sanctum` combined with the `statefulApi()` middleware group, so first-party requests (browser sessions) continue to authenticate via the session guard while external clients authenticate statelessly via the bearer token. Tokens are not susceptible to CSRF because a bearer token must be attached explicitly and is never auto-attached by the browser.

### Token Lifecycle

| Event              | Action                                                     |
| ------------------ | ---------------------------------------------------------- |
| Login              | Issue a new personal access token (`device_name` optional) |
| Logout             | Revoke the token used to authenticate the request          |
| Password change    | Revoke all user tokens (planned)                           |
| Admin deactivation | Revoke all user tokens for tenant (planned)                |
| Token expiry       | Not set by default; configurable via `sanctum.expiration`  |

### Tenant Context in API

The active tenant is resolved by `SetTenantContext` middleware in this order:

1. Session `active_tenant_id` (first-party SPA)
2. `tenant_id` request input
3. `X-Tenant-ID` request header
4. The user's first active tenant

The active branch (for dashboard and sales-summary reports) is resolved similarly from the `X-Branch-ID` header, `branch_id` query parameter, or the session `active_branch_id`.

```http
X-Tenant-ID: 01ARYZ6S41TSV4RRFFQ69G5FAV
X-Branch-ID: 01ARYZ6S41TSV4RRFFQ69G5FAV
```

---

## Response Format

### Success Response

```json
{
    "data": {}
}
```

### List Response

```json
{
    "data": []
}
```

### Create Response

```json
{
    "data": {}
}
```

_`201 Created` for resource creation, `200 OK` otherwise._

### Validation Error Response

```http
422 Unprocessable Content
```

```json
{
    "message": "The given data was invalid.",
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
    "message": "Invalid credentials."
}
```

_Unauthenticated requests are redirected to the login page for session clients, or return `401` for bearer-token clients._

### Not Found Response

```http
404 Not Found
```

---

## API Endpoint Catalogue

### Authentication

| Method | Endpoint              | Description                                     | Auth   |
| ------ | --------------------- | ----------------------------------------------- | ------ |
| POST   | `/api/v1/auth/login`  | Exchange credentials for a token (rate-limited) | None   |
| POST   | `/api/v1/auth/logout` | Revoke the current token                        | Bearer |
| GET    | `/api/v1/me`          | Current user + tenant context                   | Bearer |

#### Login Request

```json
{
    "email": "cashier@shop.com",
    "password": "secret",
    "device_name": "iPhone 15 - Cashier 1"
}
```

#### Login Response

```json
{
    "message": "Authenticated.",
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Cashier One",
        "email": "cashier@shop.com",
        "is_platform_admin": false,
        "avatar": null,
        "phone": null
    }
}
```

---

### Dashboard

| Method | Endpoint            | Description                              |
| ------ | ------------------- | ---------------------------------------- |
| GET    | `/api/v1/dashboard` | Dashboard KPIs for current tenant/branch |

---

### Products

| Method | Endpoint                     | Description                           |
| ------ | ---------------------------- | ------------------------------------- |
| GET    | `/api/v1/products`           | List products (paginated, filterable) |
| GET    | `/api/v1/products/{product}` | Single product with variants          |
| GET    | `/api/v1/products/search`    | Search by name/barcode/SKU            |

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

| Method | Endpoint                     | Description                |
| ------ | ---------------------------- | -------------------------- |
| GET    | `/api/v1/product-categories` | All categories (flat list) |

---

### Inventory

| Method | Endpoint                      | Description                     |
| ------ | ----------------------------- | ------------------------------- |
| GET    | `/api/v1/inventory`           | Current stock levels for branch |
| GET    | `/api/v1/inventory/{product}` | Stock for specific product      |

---

### Customers

| Method | Endpoint                       | Description     |
| ------ | ------------------------------ | --------------- |
| GET    | `/api/v1/customers`            | List customers  |
| POST   | `/api/v1/customers`            | Create customer |
| GET    | `/api/v1/customers/{customer}` | Single customer |
| PUT    | `/api/v1/customers/{customer}` | Update customer |

---

### Sales

| Method | Endpoint                      | Description                       |
| ------ | ----------------------------- | --------------------------------- |
| GET    | `/api/v1/sales`               | List sales (filterable by date)   |
| POST   | `/api/v1/sales`               | Create sale                       |
| GET    | `/api/v1/sales/{sale}`        | Single sale with items + payments |
| POST   | `/api/v1/sales/{sale}/return` | Create sale return                |

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
            "unit_price": 150.0,
            "discount_amount": 0
        }
    ],
    "payments": [
        {
            "payment_method_id": "...",
            "amount": 300.0
        }
    ],
    "discount_amount": 0,
    "note": null,
    "offline_created_at": null
}
```

---

### Registers

| Method | Endpoint                                     | Description                    |
| ------ | -------------------------------------------- | ------------------------------ |
| GET    | `/api/v1/registers`                          | Available registers for branch |
| GET    | `/api/v1/registers/{register}/session`       | Current open session           |
| POST   | `/api/v1/registers/{register}/open`          | Open register session          |
| POST   | `/api/v1/registers/{register}/close`         | Close register session         |
| POST   | `/api/v1/registers/{register}/cash-movement` | Record cash in/out             |

---

### Payment Methods

| Method | Endpoint                  | Description            |
| ------ | ------------------------- | ---------------------- |
| GET    | `/api/v1/payment-methods` | Active payment methods |

---

### Reports (Basic)

| Method | Endpoint                                     | Description                  |
| ------ | -------------------------------------------- | ---------------------------- |
| GET    | `/api/v1/reports/sales-summary`              | Sales summary for date range |
| GET    | `/api/v1/reports/register-session/{session}` | Register session summary     |

---

## File Structure

```
app/
└── Http/
    └── Controllers/
        └── Api/
            ├── AuthController.php
            ├── DashboardController.php
            ├── ProductController.php
            ├── ProductCategoryController.php
            ├── ProductBrandController.php
            ├── InventoryController.php
            ├── CustomerController.php
            ├── SaleController.php
            ├── RegisterController.php
            ├── PaymentMethodController.php
            └── ReportController.php
```

---

## Route Registration

```php
// routes/api.php
Route::prefix('v1')->group(function () {

    // Public routes (rate limited against brute force)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Authenticated routes — session (first-party) or bearer token (external)
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::get('/product-categories', [ProductCategoryController::class, 'index']);
        Route::get('/product-brands', [ProductBrandController::class, 'index']);
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::apiResource('/sales', SaleController::class)->only(['index', 'store', 'show']);
        Route::get('/registers', [RegisterController::class, 'index']);
        Route::get('/registers/{register}/session', [RegisterController::class, 'session']);
        Route::post('/registers/{register}/open', [RegisterController::class, 'open']);
        Route::post('/registers/{register}/close', [RegisterController::class, 'close']);
        Route::post('/registers/{register}/cash-movement', [RegisterController::class, 'cashMovement']);
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);
        Route::get('/reports/register-session/{session}', [ReportController::class, 'registerSession']);
    });
});
```

The `tenant` middleware alias resolves to `App\Http\Middleware\SetTenantContext`, which attaches the active tenant to the request as `$request->attributes->get('tenant')` and to the scoped `App\Support\TenantContext` container binding. Controllers enforce isolation with `abort_unless($model->tenant_id === $tenant?->id, 403)`.

---

## API Conventions

### Pagination

List endpoints currently return the full collection under `data`. Pagination is not yet implemented — add `?page=`, `?per_page=` support before exposing large datasets to mobile clients.

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
