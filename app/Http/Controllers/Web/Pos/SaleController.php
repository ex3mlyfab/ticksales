<?php

namespace App\Http\Controllers\Web\Pos;

use App\Actions\Sales\CreateSaleAction;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\TaxRate;
use App\Models\TenantBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        // Find open register session for the current user in the active tenant
        $openSession = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->where('opened_by', $user->id)
            ->where('status', 'open')
            ->with('register')
            ->first();

        $products = Product::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->with(['inventories' => fn ($q) => $q->where('branch_id', $openSession?->branch_id ?? null)])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'selling_price', 'tax_rate_id']);

        $taxRates = TaxRate::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'rate']);

        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        $customers = Customer::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $registers = Register::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        return Inertia::render('pos/index', [
            'openSession' => $openSession,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'customers' => $customers,
            'registers' => $registers,
            'taxRates' => $taxRates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        $tenantId = $tenant?->id;

        $validated = $request->validate([
            'register_session_id' => ['nullable', 'string', Rule::exists('register_sessions', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'customer_id' => ['nullable', 'string', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'items.*.variant_id' => ['nullable', 'string'],
            'items.*.name' => ['nullable', 'string'],
            'items.*.sku' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'string', Rule::exists('payment_methods', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'string', Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'idempotency_key' => ['nullable', 'uuid'],
            'synced_from_offline' => ['nullable', 'boolean'],
            'offline_created_at' => ['nullable', 'date'],
        ]);

        $session = isset($validated['register_session_id']) ? RegisterSession::query()->find($validated['register_session_id']) : null;
        $branchId = $session?->branch_id ?? ($validated['branch_id'] ?? null);

        $sale = CreateSaleAction::execute([
            'tenant_id' => $tenant?->id,
            'branch_id' => $branchId,
            'register_session_id' => $validated['register_session_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'sold_by' => $user->id,
            'items' => $validated['items'],
            'payments' => $validated['payments'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'note' => $validated['note'] ?? null,
            'idempotency_key' => $validated['idempotency_key'] ?? null,
            'synced_from_offline' => $validated['synced_from_offline'] ?? false,
            'offline_created_at' => $validated['offline_created_at'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sale completed.')]);

        return to_route('pos.sales.receipt', $sale);
    }

    public function receipt(Request $request, Sale $sale): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($sale->tenant_id === $tenant?->id, 403);

        $sale->load(['items.product', 'payments.paymentMethod', 'customer', 'branch', 'soldBy']);

        $branding = TenantBranding::query()
            ->where('tenant_id', $tenant?->id)
            ->first();

        return Inertia::render('pos/receipt', [
            'sale' => $sale,
            'branding' => $branding,
        ]);
    }
}
