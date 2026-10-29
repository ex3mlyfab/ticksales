<?php

namespace App\Http\Controllers\Web;

use App\Actions\Purchasing\ReceivePurchaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $purchases = Purchase::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['branch', 'supplier', 'purchaseOrder'])
            ->orderBy('purchase_date', 'desc')
            ->paginate(20);

        return Inertia::render('purchases/index', [
            'purchases' => $purchases,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return Inertia::render('purchases/create', [
            'branches' => $branches,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(StorePurchaseRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        // use ReceivePurchaseAction for consistent stock update and purchase creation
        app(ReceivePurchaseAction::class)->execute([
            'tenant_id' => $tenant?->id,
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_date' => $validated['purchase_date'],
            'invoice_no' => $validated['invoice_no'] ?? null,
            'paid_amount' => $validated['paid_amount'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'items' => $validated['items'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase recorded and stock received.')]);

        return to_route('purchases.index');
    }

    public function show(Request $request, Purchase $purchase): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchase->tenant_id === $tenant?->id, 403);

        $purchase->load(['branch', 'supplier', 'purchaseOrder', 'items.product']);

        return Inertia::render('purchases/show', [
            'purchase' => $purchase,
        ]);
    }
}
