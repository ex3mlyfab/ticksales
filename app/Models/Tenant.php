<?php

namespace App\Models;

use App\Concerns\HasSubscription;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $country
 * @property string $currency
 * @property string $timezone
 * @property string $locale
 * @property bool $is_active
 * @property Carbon|null $trial_ends_at
 * @property string|null $plan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasSubscription, HasUlids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'country',
        'currency',
        'timezone',
        'locale',
        'is_active',
        'trial_ends_at',
        'plan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the users that belong to this tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->select('users.*')
            ->withPivot(['role_id', 'is_owner', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the tenant membership records for the tenant.
     */
    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Get the branches for the tenant.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the inventory snapshots for the tenant.
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the inventory transactions for the tenant.
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get the stock adjustments for the tenant.
     */
    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Get the stock transfers for the tenant.
     */
    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    /**
     * Get the customer groups for the tenant.
     */
    public function customerGroups()
    {
        return $this->hasMany(CustomerGroup::class);
    }

    /**
     * Get the customers for the tenant.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the suppliers for the tenant.
     */
    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get the purchase orders for the tenant.
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get the purchases for the tenant.
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the purchase returns for the tenant.
     */
    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Get the sales for the tenant.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the payment methods for the tenant.
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get the POS registers for the tenant.
     */
    public function registers()
    {
        return $this->hasMany(Register::class);
    }

    /**
     * Get the register sessions for the tenant.
     */
    public function registerSessions()
    {
        return $this->hasMany(RegisterSession::class);
    }

    /**
     * Get the sale returns for the tenant.
     */
    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    /**
     * Get the expense categories for the tenant.
     */
    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    /**
     * Get the expenses for the tenant.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the cash movements for the tenant.
     */
    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }

    /**
     * Get the audit logs for the tenant.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the settings for the tenant.
     */
    public function tenantSetting()
    {
        return $this->hasOne(TenantSetting::class);
    }

    /**
     * Get the branding for the tenant.
     */
    public function tenantBranding()
    {
        return $this->hasOne(TenantBranding::class);
    }

    /**
     * Get the product categories for the tenant.
     */
    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    /**
     * Get the product brands for the tenant.
     */
    public function productBrands()
    {
        return $this->hasMany(ProductBrand::class);
    }

    /**
     * Get the product units for the tenant.
     */
    public function productUnits()
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get the tax rates for the tenant.
     */
    public function taxRates()
    {
        return $this->hasMany(TaxRate::class);
    }

    /**
     * Get the discounts for the tenant.
     */
    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Get the products for the tenant.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the product variants for the tenant.
     */
    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the product barcodes for the tenant.
     */
    public function productBarcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }
}
