# Database Design

## Primary Key Strategy

All application-owned tables use **ULID string primary keys** (`char(26)`).  
Foreign keys to application tables use `foreignUlid()`.  
The `users` table is a framework-managed table using integer PK — FKs to `users` use `foreignId('user_id')`.

See the [ULID Primary Keys skill](../.agents/skills/ulid-primary-keys/SKILL.md) for full rules.

---

## Entity Relationship Overview

```
Platform Layer
├── users (framework — integer PK)
├── tenants
├── tenant_users (users ↔ tenants)
├── audit_logs

Tenant Configuration
├── tenant_settings (1:1 with tenants)
├── tenant_branding (1:1 with tenants)
├── branches (many per tenant)
├── roles (many per tenant)
├── permissions (platform-defined)
├── role_permissions

Product Catalogue
├── product_categories
├── product_brands
├── product_units
├── products
├── product_variants
├── product_barcodes
├── tax_rates
├── discounts

Inventory
├── inventories (current stock per product+branch)
├── inventory_transactions (full movement ledger)
├── stock_adjustments
├── stock_adjustment_items
├── stock_transfers
├── stock_transfer_items

Parties
├── customers
├── customer_groups
├── suppliers

Purchasing
├── purchase_orders
├── purchase_order_items
├── purchases
├── purchase_items
├── purchase_returns
├── purchase_return_items

POS
├── payment_methods
├── registers
├── register_sessions
├── sales
├── sale_items
├── sale_payments
├── sale_returns
├── sale_return_items

Financial
├── expenses
├── expense_categories
├── cash_movements

Documents
├── receipts
├── invoices
```

---

## Table Definitions

### Platform Layer

#### `tenants`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| name | varchar(255) | Business name |
| slug | varchar(100) UNIQUE | URL identifier |
| email | varchar(255) | Contact email |
| phone | varchar(50) nullable | |
| address | text nullable | |
| country | char(2) nullable | ISO country code |
| currency | char(3) default 'USD' | ISO currency code |
| timezone | varchar(50) default 'UTC' | |
| locale | varchar(10) default 'en' | |
| is_active | boolean default true | |
| trial_ends_at | timestamp nullable | |
| plan | varchar(50) nullable | Subscription plan |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | Soft delete |

Indexes: `slug`, `is_active`

#### `tenant_users`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | CASCADE delete |
| user_id | bigint unsigned FK → users | CASCADE delete |
| role_id | char(26) FK → roles nullable | |
| is_owner | boolean default false | Tenant owner |
| is_active | boolean default true | |
| joined_at | timestamp | |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique: `(tenant_id, user_id)`  
Indexes: `tenant_id`, `user_id`

#### `audit_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) nullable | null = platform action |
| branch_id | char(26) nullable | |
| user_id | bigint unsigned nullable | Actor |
| event | varchar(100) | e.g. 'sale.created' |
| auditable_type | varchar(255) nullable | Morph type |
| auditable_id | varchar(26) nullable | Morph ID |
| old_values | json nullable | Before state |
| new_values | json nullable | After state |
| ip_address | varchar(45) nullable | |
| user_agent | varchar(1024) nullable | |
| created_at | timestamp | |

Indexes: `tenant_id`, `user_id`, `event`, `auditable_type+auditable_id`

---

### Tenant Configuration

#### `tenant_settings`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants UNIQUE | 1:1 |
| tax_inclusive | boolean default false | |
| default_tax_rate_id | char(26) nullable | |
| receipt_footer | text nullable | |
| invoice_prefix | varchar(20) default 'INV' | |
| receipt_prefix | varchar(20) default 'REC' | |
| po_prefix | varchar(20) default 'PO' | |
| stock_alert_threshold | int default 10 | |
| allow_negative_stock | boolean default false | |
| require_customer_on_sale | boolean default false | |
| loyalty_enabled | boolean default false | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `tenant_branding`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants UNIQUE | 1:1 |
| logo_path | varchar(255) nullable | |
| favicon_path | varchar(255) nullable | |
| primary_color | char(7) nullable | Hex |
| secondary_color | char(7) nullable | |
| accent_color | char(7) nullable | |
| sidebar_style | varchar(50) default 'default' | |
| border_radius | varchar(20) default '0.625rem' | |
| theme_preset | varchar(50) default 'neutral' | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `branches`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(255) | |
| code | varchar(50) nullable | Branch code |
| address | text nullable | |
| phone | varchar(50) nullable | |
| email | varchar(255) nullable | |
| is_main | boolean default false | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | |

Indexes: `tenant_id`, `(tenant_id, code)`

#### `roles`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants nullable | null = platform role |
| name | varchar(100) | |
| slug | varchar(100) | e.g. 'cashier' |
| description | text nullable | |
| is_system | boolean default false | Cannot be deleted |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique: `(tenant_id, slug)`

#### `permissions`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| name | varchar(100) UNIQUE | e.g. 'products.create' |
| description | text nullable | |
| group | varchar(50) | e.g. 'products' |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `role_permissions`
| Column | Type | Notes |
|--------|------|-------|
| role_id | char(26) FK → roles | |
| permission_id | char(26) FK → permissions | |

PK: `(role_id, permission_id)`

---

### Product Catalogue

#### `product_categories`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| parent_id | char(26) FK → product_categories nullable | Nested |
| name | varchar(255) | |
| slug | varchar(255) | |
| description | text nullable | |
| image_path | varchar(255) nullable | |
| sort_order | int default 0 | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: `tenant_id`, `(tenant_id, slug)`

#### `product_brands`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(255) | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `product_units`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(100) | e.g. 'Kilogram' |
| abbreviation | varchar(20) | e.g. 'kg' |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `products`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| category_id | char(26) FK → product_categories nullable | |
| brand_id | char(26) FK → product_brands nullable | |
| unit_id | char(26) FK → product_units nullable | |
| name | varchar(255) | |
| sku | varchar(100) nullable | |
| barcode | varchar(100) nullable | |
| description | text nullable | |
| cost_price | decimal(15,4) default 0 | |
| selling_price | decimal(15,4) default 0 | |
| tax_rate_id | char(26) FK → tax_rates nullable | |
| has_variants | boolean default false | |
| track_inventory | boolean default true | |
| alert_quantity | int default 0 | |
| image_path | varchar(255) nullable | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | |

Indexes: `tenant_id`, `(tenant_id, sku)`, `(tenant_id, barcode)`, `category_id`

#### `product_variants`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| product_id | char(26) FK → products | |
| tenant_id | char(26) FK → tenants | |
| name | varchar(255) | e.g. 'Red / Large' |
| sku | varchar(100) nullable | |
| barcode | varchar(100) nullable | |
| cost_price | decimal(15,4) nullable | Overrides product |
| selling_price | decimal(15,4) nullable | Overrides product |
| image_path | varchar(255) nullable | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `tax_rates`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(100) | e.g. 'VAT 16%' |
| rate | decimal(8,4) | Percentage, e.g. 16.0000 |
| is_inclusive | boolean default false | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `discounts`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(255) | |
| type | enum('percentage','fixed') | |
| value | decimal(15,4) | |
| min_purchase_amount | decimal(15,4) nullable | |
| max_discount_amount | decimal(15,4) nullable | |
| starts_at | timestamp nullable | |
| ends_at | timestamp nullable | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### Inventory

#### `inventories`
Current stock snapshot per product (or variant) per branch.
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| product_id | char(26) FK → products | |
| variant_id | char(26) FK → product_variants nullable | |
| quantity | decimal(15,4) default 0 | |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique: `(branch_id, product_id, variant_id)`  
Indexes: `tenant_id`, `(branch_id, product_id)`

#### `inventory_transactions`
Immutable ledger of every stock movement.
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| product_id | char(26) FK → products | |
| variant_id | char(26) FK → product_variants nullable | |
| reference_type | varchar(100) | Morph: 'Sale', 'Purchase', etc. |
| reference_id | char(26) | Morph ID |
| type | enum | Purchase, Sale, Return, Adjustment, Transfer, Opening, Damage |
| quantity | decimal(15,4) | Positive = in, negative = out |
| unit_cost | decimal(15,4) nullable | |
| note | text nullable | |
| created_by | bigint unsigned nullable FK → users | |
| created_at | timestamp | |

Indexes: `tenant_id`, `(branch_id, product_id)`, `reference_type+reference_id`

#### `stock_adjustments`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| reference_no | varchar(100) | Auto-generated |
| reason | text nullable | |
| status | enum('draft','completed') | |
| adjusted_by | bigint unsigned FK → users | |
| adjusted_at | timestamp nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `stock_adjustment_items`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| adjustment_id | char(26) FK → stock_adjustments | |
| product_id | char(26) FK → products | |
| variant_id | char(26) FK → product_variants nullable | |
| quantity_before | decimal(15,4) | |
| quantity_after | decimal(15,4) | |
| quantity_adjusted | decimal(15,4) | Computed: after - before |
| unit_cost | decimal(15,4) nullable | |

#### `stock_transfers`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| from_branch_id | char(26) FK → branches | |
| to_branch_id | char(26) FK → branches | |
| reference_no | varchar(100) | |
| status | enum('pending','in_transit','completed','cancelled') | |
| note | text nullable | |
| transferred_by | bigint unsigned FK → users | |
| transferred_at | timestamp nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### Parties

#### `customers`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| customer_group_id | char(26) FK → customer_groups nullable | |
| name | varchar(255) | |
| email | varchar(255) nullable | |
| phone | varchar(50) nullable | |
| address | text nullable | |
| credit_limit | decimal(15,4) default 0 | |
| outstanding_balance | decimal(15,4) default 0 | |
| loyalty_points | int default 0 | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | |

Indexes: `tenant_id`, `(tenant_id, phone)`, `(tenant_id, email)`

#### `suppliers`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(255) | |
| email | varchar(255) nullable | |
| phone | varchar(50) nullable | |
| address | text nullable | |
| tax_number | varchar(100) nullable | |
| outstanding_balance | decimal(15,4) default 0 | |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp nullable | |

---

### POS

#### `payment_methods`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(100) | e.g. 'Cash', 'Card', 'M-Pesa' |
| type | enum('cash','card','mobile','transfer','credit','other') | |
| is_active | boolean default true | |
| sort_order | int default 0 | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `registers`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| name | varchar(100) | e.g. 'Counter 1' |
| is_active | boolean default true | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `register_sessions`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| register_id | char(26) FK → registers | |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| opened_by | bigint unsigned FK → users | |
| closed_by | bigint unsigned FK → users nullable | |
| opening_balance | decimal(15,4) default 0 | |
| closing_balance | decimal(15,4) nullable | |
| expected_balance | decimal(15,4) nullable | Calculated at close |
| actual_balance | decimal(15,4) nullable | Counted cash |
| variance | decimal(15,4) nullable | |
| notes | text nullable | |
| status | enum('open','closed') | |
| opened_at | timestamp | |
| closed_at | timestamp nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: `(register_id, status)`, `opened_by`, `tenant_id`

#### `sales`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| register_session_id | char(26) FK → register_sessions nullable | |
| customer_id | char(26) FK → customers nullable | |
| reference_no | varchar(100) | Auto-generated |
| idempotency_key | char(36) UNIQUE | Client UUID (offline safety) |
| status | enum('pending','completed','refunded','partially_refunded','cancelled') | |
| subtotal | decimal(15,4) | |
| discount_amount | decimal(15,4) default 0 | |
| tax_amount | decimal(15,4) default 0 | |
| total_amount | decimal(15,4) | |
| paid_amount | decimal(15,4) default 0 | |
| change_amount | decimal(15,4) default 0 | |
| outstanding_amount | decimal(15,4) default 0 | |
| note | text nullable | |
| sold_by | bigint unsigned FK → users | |
| sold_at | timestamp | |
| synced_from_offline | boolean default false | |
| offline_created_at | timestamp nullable | |
| created_at | timestamp | |
| updated_at | timestamp | |

Indexes: `tenant_id`, `branch_id`, `(tenant_id, reference_no)`, `customer_id`, `register_session_id`, `idempotency_key`

#### `sale_items`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| sale_id | char(26) FK → sales | CASCADE |
| product_id | char(26) FK → products | |
| variant_id | char(26) FK → product_variants nullable | |
| name | varchar(255) | Snapshot at time of sale |
| sku | varchar(100) nullable | Snapshot |
| quantity | decimal(15,4) | |
| unit_price | decimal(15,4) | |
| cost_price | decimal(15,4) nullable | For margin calculation |
| discount_amount | decimal(15,4) default 0 | |
| tax_amount | decimal(15,4) default 0 | |
| tax_rate | decimal(8,4) default 0 | Rate at time of sale |
| total_amount | decimal(15,4) | |
| created_at | timestamp | |

Indexes: `sale_id`, `product_id`

#### `sale_payments`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| sale_id | char(26) FK → sales | |
| payment_method_id | char(26) FK → payment_methods | |
| amount | decimal(15,4) | |
| reference | varchar(255) nullable | e.g. card auth code |
| created_at | timestamp | |

#### `sale_returns`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| sale_id | char(26) FK → sales | |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| reference_no | varchar(100) | |
| reason | text nullable | |
| refund_amount | decimal(15,4) | |
| refund_method_id | char(26) FK → payment_methods nullable | |
| status | enum('pending','completed') | |
| returned_by | bigint unsigned FK → users | |
| returned_at | timestamp | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### Financial

#### `expenses`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| branch_id | char(26) FK → branches | |
| expense_category_id | char(26) FK → expense_categories nullable | |
| reference_no | varchar(100) nullable | |
| description | varchar(255) | |
| amount | decimal(15,4) | |
| expense_date | date | |
| payment_method_id | char(26) FK → payment_methods nullable | |
| note | text nullable | |
| attachment_path | varchar(255) nullable | |
| recorded_by | bigint unsigned FK → users | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `expense_categories`
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| tenant_id | char(26) FK → tenants | |
| name | varchar(100) | |
| created_at | timestamp | |
| updated_at | timestamp | |

#### `cash_movements`
Tracks manual cash in/out during register sessions.
| Column | Type | Notes |
|--------|------|-------|
| id | char(26) PK | ULID |
| register_session_id | char(26) FK → register_sessions | |
| tenant_id | char(26) FK → tenants | |
| type | enum('cash_in','cash_out') | |
| amount | decimal(15,4) | |
| reason | varchar(255) nullable | |
| recorded_by | bigint unsigned FK → users | |
| created_at | timestamp | |

---

## Inventory Accounting Logic

Every stock-changing event creates an `inventory_transactions` record and updates the `inventories` aggregate:

```
Purchase completed
    → inventory_transactions (type=Purchase, qty=+N)
    → inventories.quantity += N

Sale completed
    → inventory_transactions (type=Sale, qty=-N)
    → inventories.quantity -= N

Return received
    → inventory_transactions (type=SaleReturn, qty=+N)
    → inventories.quantity += N

Stock adjusted
    → inventory_transactions (type=Adjustment, qty=delta)
    → inventories.quantity = new_value

Stock transferred (from Branch A)
    → inventory_transactions (branch=A, type=TransferOut, qty=-N)
    → inventories[branch_A].quantity -= N

Stock transferred (to Branch B)
    → inventory_transactions (branch=B, type=TransferIn, qty=+N)
    → inventories[branch_B].quantity += N
```

All inventory operations run inside database transactions to prevent inconsistent state.

---

## Indexing Strategy

| Table | Index | Purpose |
|-------|-------|---------|
| All tenant-scoped | `tenant_id` | Tenant isolation queries |
| inventories | `(branch_id, product_id)` | Stock lookup |
| inventory_transactions | `(branch_id, product_id, created_at)` | Movement history |
| sales | `(tenant_id, sold_at)` | Revenue reports |
| sales | `idempotency_key` | Dedup offline sales |
| products | `(tenant_id, sku)` | SKU lookup |
| products | `(tenant_id, barcode)` | Barcode scan |
| audit_logs | `(tenant_id, event, created_at)` | Audit queries |
| register_sessions | `(register_id, status)` | Open session check |

---

## Soft Deletes

Applied to: `tenants`, `branches`, `products`, `customers`, `suppliers`  
Not applied to: `sales`, `inventory_transactions`, `audit_logs` (immutable records)
