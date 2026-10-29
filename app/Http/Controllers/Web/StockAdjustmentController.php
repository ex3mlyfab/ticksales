<?php

namespace App\Http\Controllers\Web;

use App\Actions\Inventory\AdjustInventoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustment\PostStockAdjustmentRequest;
use App\Http\Requests\StockAdjustment\StoreStockAdjustmentRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of stock adjustments.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $adjustments = StockAdjustment::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['branch', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('stock-adjustments/index', [
            'adjustments' => $adjustments,
        ]);
    }

    /**
     * Show the form for creating a new stock adjustment.
     */
    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return Inertia::render('stock-adjustments/create', [
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created stock adjustment.
     */
    public function store(StoreStockAdjustmentRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $referenceNo = 'ADJ-'.now()->format('Ymd-His').'-'.strtoupper(
            Str::random(6)
        );

        $adjustment = StockAdjustment::create([
            'tenant_id' => $tenant?->id,
            'branch_id' => $validated['branch_id'],
            'reference_no' => $referenceNo,
            'reason' => $validated['reason'] ?? null,
            'status' => 'draft',
            'adjusted_by' => $request->user()?->id,
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::query()
                ->where('tenant_id', $tenant?->id)
                ->findOrFail($item['product_id']);

            $quantityBefore = 0.0;
            $quantityAdjusted = (float) $item['quantity_adjusted'];
            $quantityAfter = $quantityBefore + $quantityAdjusted;

            StockAdjustmentItem::create([
                'adjustment_id' => $adjustment->id,
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'] ?? null,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'quantity_adjusted' => $quantityAdjusted,
                'unit_cost' => $item['unit_cost'] ?? null,
            ]);
        }

        $auditService->created($adjustment, ['event' => 'stock_adjustment.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock adjustment created.')]);

        return to_route('stock-adjustments.index');
    }

    /**
     * Post (finalize) the stock adjustment.
     */
    public function post(PostStockAdjustmentRequest $request, StockAdjustment $stockAdjustment, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($stockAdjustment->tenant_id === $tenant?->id, 403);

        if ($stockAdjustment->status !== 'draft') {
            return to_route('stock-adjustments.index');
        }

        $stockAdjustment->update([
            'status' => 'posted',
            'adjusted_at' => now(),
        ]);

        // Debit or credit based on quantity_adjusted sign
        foreach ($stockAdjustment->items as $item) {
            // Update inventory for each item
            AdjustInventoryAction::handle(
                tenantId: $tenant->id,
                branchId: $stockAdjustment->branch_id,
                productId: $item->product_id,
                variantId: $item->variant_id,
                quantityChanged: $item->quantity_adjusted,
                referenceType: 'stock_adjustment',
                referenceId: $stockAdjustment->id,
                type: 'adjustment',
                note: $stockAdjustment->reason,
            );
        }

        $auditService->updated($stockAdjustment, ['event' => 'stock_adjustment.posted']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock adjustment posted.')]);

        return to_route('stock-adjustments.index');
    }

    /**
     * Remove the stock adjustment.
     */
    public function destroy(Request $request, StockAdjustment $stockAdjustment, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($stockAdjustment->tenant_id === $tenant?->id, 403);

        $auditService->deleted($stockAdjustment);

        $stockAdjustment->items()->delete();
        $stockAdjustment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock adjustment deleted.')]);

        return to_route('stock-adjustments.index');
    }
}
