<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturn\StorePurchaseReturnRequest;
use App\Http\Requests\PurchaseReturn\UpdatePurchaseReturnRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $returns = PurchaseReturn::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['branch', 'supplier', 'purchase'])
            ->orderBy('return_date', 'desc')
            ->paginate(20);

        return Inertia::render('purchase-returns/index', [
            'returns' => $returns,
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

        return Inertia::render('purchase-returns/create', [
            'branches' => $branches,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(StorePurchaseReturnRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        $return = PurchaseReturn::query()->create([
            'tenant_id' => $tenant?->id,
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_id' => $validated['purchase_id'] ?? null,
            'reference_no' => 'PR-'.now()->format('Y-m-d').'-'.str_pad((string) PurchaseReturn::where('tenant_id', $tenant?->id)->count(), 5, '0', STR_PAD_LEFT),
            'reason' => $validated['reason'],
            'return_date' => $validated['return_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_cost'];
            $discount = $item['discount_amount'] ?? 0;
            $taxRate = $item['tax_rate'] ?? 0;
            $lineTaxable = $lineSubtotal - $discount;
            $lineTax = $lineTaxable * ($taxRate / 100);
            $lineTotal = $lineTaxable + $lineTax;

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
            $discountAmount += $discount;

            PurchaseReturnItem::query()->create([
                'purchase_return_id' => $return->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'tax_rate' => $taxRate,
                'discount_amount' => $discount,
                'line_total' => $lineTotal,
            ]);
        }

        $return->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $subtotal + $taxAmount - $discountAmount,
        ]);

        $auditService->created($return, ['event' => 'purchase_return.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase return created.')]);

        return to_route('purchase-returns.index');
    }

    public function show(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseReturn->tenant_id === $tenant?->id, 403);

        $purchaseReturn->load(['branch', 'supplier', 'purchase', 'items.product']);

        return Inertia::render('purchase-returns/show', [
            'return' => $purchaseReturn,
        ]);
    }

    public function edit(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseReturn->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseReturn->status === 'draft', 403, 'Cannot edit a posted purchase return.');

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

        $purchaseReturn->load(['items.product']);

        return Inertia::render('purchase-returns/edit', [
            'return' => $purchaseReturn,
            'branches' => $branches,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function update(UpdatePurchaseReturnRequest $request, PurchaseReturn $purchaseReturn, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseReturn->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseReturn->status === 'draft', 403, 'Cannot update a posted purchase return.');

        $validated = $request->validated();

        $purchaseReturn->update([
            'branch_id' => $validated['branch_id'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_id' => $validated['purchase_id'] ?? null,
            'reason' => $validated['reason'],
            'return_date' => $validated['return_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;

        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_cost'];
            $discount = $item['discount_amount'] ?? 0;
            $taxRate = $item['tax_rate'] ?? 0;
            $lineTaxable = $lineSubtotal - $discount;
            $lineTax = $lineTaxable * ($taxRate / 100);
            $lineTotal = $lineTaxable + $lineTax;

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
            $discountAmount += $discount;
        }

        $purchaseReturn->items()->delete();

        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_cost'];
            $discount = $item['discount_amount'] ?? 0;
            $taxRate = $item['tax_rate'] ?? 0;
            $lineTaxable = $lineSubtotal - $discount;
            $lineTax = $lineTaxable * ($taxRate / 100);
            $lineTotal = $lineTaxable + $lineTax;

            PurchaseReturnItem::query()->create([
                'purchase_return_id' => $purchaseReturn->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'tax_rate' => $taxRate,
                'discount_amount' => $discount,
                'line_total' => $lineTotal,
            ]);
        }

        $purchaseReturn->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $subtotal + $taxAmount - $discountAmount,
        ]);

        $auditService->updated($purchaseReturn, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase return updated.')]);

        return to_route('purchase-returns.index');
    }

    public function post(Request $request, PurchaseReturn $purchaseReturn, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseReturn->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseReturn->status === 'draft', 403, 'Only draft returns can be posted.');

        DB::transaction(function () use ($purchaseReturn, $tenant) {
            foreach ($purchaseReturn->items as $item) {
                // Decrease inventory
                $inventory = Inventory::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenant?->id,
                        'branch_id' => $purchaseReturn->branch_id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                    ],
                    ['outstanding_balance' => 0, 'stock_level' => 0]
                );

                $inventory->lockForUpdate();
                $inventory->decrement('stock_level', $item->quantity);
                $inventory->save();

                // Log inventory transaction
                InventoryTransaction::query()->create([
                    'tenant_id' => $tenant?->id,
                    'branch_id' => $purchaseReturn->branch_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => -$item->quantity,
                    'type' => 'purchase_return',
                    'reference_id' => $purchaseReturn->id,
                    'reference_type' => PurchaseReturn::class,
                    'notes' => 'Stock returned to supplier '.$purchaseReturn->reference_no,
                ]);
            }

            $purchaseReturn->update(['status' => 'posted']);
        });

        $auditService->updated($purchaseReturn, ['status' => 'posted']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase return posted and stock adjusted.')]);

        return to_route('purchase-returns.index');
    }

    public function destroy(Request $request, PurchaseReturn $purchaseReturn, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($purchaseReturn->tenant_id === $tenant?->id, 403);
        abort_unless($purchaseReturn->status === 'draft', 403, 'Cannot delete a posted purchase return.');

        $auditService->deleted($purchaseReturn);

        $purchaseReturn->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase return deleted.')]);

        return to_route('purchase-returns.index');
    }
}
