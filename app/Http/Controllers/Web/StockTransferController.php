<?php

namespace App\Http\Controllers\Web;

use App\Actions\Inventory\AdjustInventoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockTransfer\PostStockTransferRequest;
use App\Http\Requests\StockTransfer\StoreStockTransferRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    /**
     * Display a listing of stock transfers.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $transfers = StockTransfer::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['fromBranch', 'toBranch'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('stock-transfers/index', [
            'transfers' => $transfers,
        ]);
    }

    /**
     * Show the form for creating a new stock transfer.
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

        return Inertia::render('stock-transfers/create', [
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created stock transfer.
     */
    public function store(StoreStockTransferRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $referenceNo = 'TRF-'.now()->format('Ymd-His').'-'.strtoupper(
            Str::random(6)
        );

        $transfer = StockTransfer::create([
            'tenant_id' => $tenant?->id,
            'from_branch_id' => $validated['from_branch_id'],
            'to_branch_id' => $validated['to_branch_id'],
            'reference_no' => $referenceNo,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
            'transferred_by' => $request->user()?->id,
        ]);

        foreach ($validated['items'] as $item) {
            StockTransferItem::create([
                'transfer_id' => $transfer->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? null,
            ]);
        }

        $auditService->created($transfer, ['event' => 'stock_transfer.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock transfer created.')]);

        return to_route('stock-transfers.index');
    }

    /**
     * Post (finalize) the stock transfer.
     */
    public function post(PostStockTransferRequest $request, StockTransfer $stockTransfer, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($stockTransfer->tenant_id === $tenant?->id, 403);

        if ($stockTransfer->status !== 'pending') {
            return to_route('stock-transfers.index');
        }

        $stockTransfer->update([
            'status' => 'posted',
            'transferred_at' => now(),
        ]);

        foreach ($stockTransfer->items as $item) {
            // Subtract from source branch
            AdjustInventoryAction::handle(
                tenantId: $tenant->id,
                branchId: $stockTransfer->from_branch_id,
                productId: $item->product_id,
                variantId: $item->variant_id,
                quantityChanged: -$item->quantity,
                referenceType: 'stock_transfer',
                referenceId: $stockTransfer->id,
                type: 'transfer_out',
                note: $stockTransfer->note,
            );

            // Add to destination branch
            AdjustInventoryAction::handle(
                tenantId: $tenant->id,
                branchId: $stockTransfer->to_branch_id,
                productId: $item->product_id,
                variantId: $item->variant_id,
                quantityChanged: $item->quantity,
                referenceType: 'stock_transfer',
                referenceId: $stockTransfer->id,
                type: 'transfer_in',
                note: $stockTransfer->note,
            );
        }

        $auditService->updated($stockTransfer, ['event' => 'stock_transfer.posted']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock transfer posted.')]);

        return to_route('stock-transfers.index');
    }

    /**
     * Remove the stock transfer.
     */
    public function destroy(Request $request, StockTransfer $stockTransfer, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($stockTransfer->tenant_id === $tenant?->id, 403);

        // Only allow deleting pending transfers
        abort_unless($stockTransfer->status === 'pending', 403);

        $auditService->deleted($stockTransfer);

        $stockTransfer->items()->delete();
        $stockTransfer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock transfer deleted.')]);

        return to_route('stock-transfers.index');
    }
}
