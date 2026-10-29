<?php

namespace App\Http\Controllers\Web;

use App\Actions\Purchasing\ReceivePurchaseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $orders = PurchaseOrder::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['branch', 'supplier', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('purchase-orders/index', [
            'orders' => $orders,
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

        return Inertia::render('purchase-orders/create', [
            'branches' => $branches,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        $order = PurchaseOrder::query()->create([
            'tenant_id' => $tenant?->id,
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'reference_no' => 'PO-'.now()->format('Y-m-d').'-'.str_pad((string) PurchaseOrder::where('tenant_id', $tenant?->id)->count(), 5, '0', STR_PAD_LEFT),
            'status' => 'draft',
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $taxRate = $item['tax_rate'] ?? 0;
            $discount = $item['discount_amount'] ?? 0;
            $lineTotal = $lineTotal - $discount;

            $subtotal += $item['quantity'] * $item['unit_cost'];
            $taxAmount += $lineTotal * ($taxRate / 100);
            $discountAmount += $discount;

            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'tax_rate' => $taxRate,
                'discount_amount' => $discount,
                'line_total' => $lineTotal + ($lineTotal * ($taxRate / 100)),
            ]);
        }

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $subtotal + $taxAmount - $discountAmount,
        ]);

        $auditService->created($order, ['event' => 'purchase_order.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order created.')]);

        return to_route('purchase-orders.index');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseOrder->tenant_id === $tenant?->id, 403);

        $purchaseOrder->load(['branch', 'supplier', 'items.product']);

        return Inertia::render('purchase-orders/show', [
            'order' => $purchaseOrder,
        ]);
    }

    public function edit(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseOrder->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseOrder->status === 'draft', 403, 'Cannot edit a posted purchase order.');

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

        $purchaseOrder->load(['items.product']);

        return Inertia::render('purchase-orders/edit', [
            'order' => $purchaseOrder,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseOrder->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseOrder->status === 'draft', 403, 'Cannot update a posted purchase order.');

        $validated = $request->validated();

        $purchaseOrder->update([
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Recalculate totals and replace items
        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $taxRate = $item['tax_rate'] ?? 0;
            $discount = $item['discount_amount'] ?? 0;
            $lineTotal = $lineTotal - $discount;

            $subtotal += $item['quantity'] * $item['unit_cost'];
            $taxAmount += $lineTotal * ($taxRate / 100);
            $discountAmount += $discount;
        }

        $purchaseOrder->items()->delete();

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_cost'];
            $taxRate = $item['tax_rate'] ?? 0;
            $discount = $item['discount_amount'] ?? 0;
            $lineTotal = $lineTotal - $discount;

            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'tax_rate' => $taxRate,
                'discount_amount' => $discount,
                'line_total' => $lineTotal + ($lineTotal * ($taxRate / 100)),
            ]);
        }

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $subtotal + $taxAmount - $discountAmount,
        ]);

        $auditService->updated($purchaseOrder, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order updated.')]);

        return to_route('purchase-orders.index');
    }

    /**
     * Post a purchase order (converts it to a purchase).
     */
    public function post(Request $request, PurchaseOrder $purchaseOrder, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseOrder->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseOrder->status === 'draft', 403, 'Only draft orders can be posted.');

        // Convert to a Purchase using ReceivePurchaseAction
        ReceivePurchaseAction::execute([
            'tenant_id' => $tenant?->id,
            'purchase_order_id' => $purchaseOrder->id,
            'branch_id' => $purchaseOrder->branch_id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'purchase_date' => $purchaseOrder->order_date,
            'items' => $purchaseOrder->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'tax_rate' => $item->tax_rate,
                'discount_amount' => $item->discount_amount,
            ])->all(),
        ]);

        $purchaseOrder->update(['status' => 'posted']);

        $auditService->updated($purchaseOrder, ['status' => 'posted']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order posted and stock received.')]);

        return to_route('purchase-orders.index');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseOrder->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseOrder->status === 'draft', 403, 'Cannot delete a posted purchase order.');

        $auditService->deleted($purchaseOrder);

        $purchaseOrder->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order deleted.')]);

        return to_route('purchase-orders.index');
    }
}
