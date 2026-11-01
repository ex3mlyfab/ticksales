<?php

namespace App\Http\Controllers\Web;

use App\Actions\Sales\RefundSaleAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleReturnResource;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $returns = SaleReturn::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['sale.customer', 'branch', 'returnedBy', 'refundMethod', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('sale-returns/index', [
            'returns' => SaleReturnResource::collection($returns),
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $sales = Sale::query()
            ->where('tenant_id', $tenant?->id)
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->with(['items.product', 'customer', 'branch', 'soldBy', 'returns.items'])
            ->orderBy('sold_at', 'desc')
            ->limit(50)
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        return Inertia::render('sale-returns/create', [
            'sales' => $sales,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        $validated = $request->validate([
            'sale_id' => ['required', 'string', Rule::exists('sales', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant?->id))],
            'refund_method_id' => ['nullable', 'string', Rule::exists('payment_methods', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant?->id))],
            'reason' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', Rule::exists('products', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant?->id))],
            'items.*.variant_id' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $sale = Sale::query()->findOrFail($validated['sale_id']);
        abort_unless($sale->tenant_id === $tenant?->id, 403);

        try {
            $saleReturn = (new RefundSaleAction)->execute([
                'sale_id' => $validated['sale_id'],
                'tenant_id' => $tenant?->id,
                'branch_id' => $sale->branch_id,
                'refund_method_id' => $validated['refund_method_id'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'returned_by' => $user->id,
                'items' => $validated['items'],
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Refund processed.')]);

            return to_route('sale-returns.show', $saleReturn);
        } catch (\RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }

    public function show(Request $request, SaleReturn $saleReturn): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($saleReturn->tenant_id === $tenant?->id, 403);

        $saleReturn->load(['items.product', 'sale.customer', 'sale.branch', 'branch', 'returnedBy', 'refundMethod']);

        return Inertia::render('sale-returns/show', [
            'saleReturn' => new SaleReturnResource($saleReturn),
        ]);
    }
}
