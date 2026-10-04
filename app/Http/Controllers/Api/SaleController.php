<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    /**
     * Display the sales for the active tenant.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $sales = Sale::query()
            ->with(['items', 'payments'])
            ->where('tenant_id', $tenant?->id)
            ->orderBy('sold_at', 'desc')
            ->get()
            ->map(fn (Sale $sale) => $this->serializeSale($sale));

        return response()->json([
            'data' => $sales,
        ]);
    }

    /**
     * Display the specified sale.
     */
    public function show(Request $request, string $sale)
    {
        $tenant = $request->attributes->get('tenant');

        $saleModel = Sale::query()
            ->with(['items', 'payments'])
            ->where('tenant_id', $tenant?->id)
            ->findOrFail($sale);

        return response()->json([
            'data' => $this->serializeSale($saleModel),
        ]);
    }

    /**
     * Store a new sale in the active tenant.
     */
    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'branch_id' => ['required', 'string'],
            'register_session_id' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string'],
            'reference_no' => ['required', 'string', 'max:255', Rule::unique('sales', 'reference_no')->where(fn ($query) => $query->where('tenant_id', $tenant?->id))],
            'idempotency_key' => ['required', 'uuid'],
            'status' => ['sometimes', 'string', 'in:completed,refunded,partial,voided'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.variant_id' => ['nullable', 'string'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_amount' => ['required', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'string'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = Branch::query()->where('tenant_id', $tenant?->id)->findOrFail($validated['branch_id']);

        if (! empty($validated['register_session_id'])) {
            RegisterSession::query()
                ->where('tenant_id', $tenant?->id)
                ->where('branch_id', $branch->id)
                ->findOrFail($validated['register_session_id']);
        }

        if (! empty($validated['customer_id'])) {
            Customer::query()->where('tenant_id', $tenant?->id)->findOrFail($validated['customer_id']);
        }

        $items = $validated['items'];
        $payments = $validated['payments'];

        $subtotal = collect($items)->sum(fn (array $item) => (float) ($item['unit_price'] ?? 0) * (float) ($item['quantity'] ?? 0));
        $discountAmount = collect($items)->sum(fn (array $item) => (float) ($item['discount_amount'] ?? 0));
        $taxAmount = collect($items)->sum(fn (array $item) => (float) ($item['tax_amount'] ?? 0));
        $totalAmount = collect($items)->sum(fn (array $item) => (float) ($item['total_amount'] ?? 0));
        $paidAmount = collect($payments)->sum(fn (array $item) => (float) ($item['amount'] ?? 0));

        $sale = DB::transaction(function () use ($request, $tenant, $validated, $branch, $subtotal, $discountAmount, $taxAmount, $totalAmount, $paidAmount) {
            $saleModel = Sale::query()->create([
                'tenant_id' => $tenant?->id,
                'branch_id' => $branch->id,
                'register_session_id' => $validated['register_session_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'reference_no' => $validated['reference_no'],
                'idempotency_key' => $validated['idempotency_key'],
                'status' => $validated['status'] ?? 'completed',
                'subtotal' => number_format($subtotal, 4, '.', ''),
                'discount_amount' => number_format($discountAmount, 4, '.', ''),
                'tax_amount' => number_format($taxAmount, 4, '.', ''),
                'total_amount' => number_format($totalAmount, 4, '.', ''),
                'paid_amount' => number_format($paidAmount, 4, '.', ''),
                'change_amount' => number_format(max(0, $paidAmount - $totalAmount), 4, '.', ''),
                'outstanding_amount' => number_format(max(0, $totalAmount - $paidAmount), 4, '.', ''),
                'note' => $validated['note'] ?? null,
                'sold_by' => $request->user()?->id,
                'sold_at' => now(),
                'synced_from_offline' => false,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::query()->where('tenant_id', $tenant?->id)->findOrFail($item['product_id']);

                SaleItem::query()->create([
                    'sale_id' => $saleModel->id,
                    'product_id' => $product->id,
                    'variant_id' => $item['variant_id'] ?? null,
                    'name' => $item['name'],
                    'sku' => $item['sku'] ?? $product->sku,
                    'quantity' => number_format((float) $item['quantity'], 4, '.', ''),
                    'unit_price' => number_format((float) $item['unit_price'], 4, '.', ''),
                    'cost_price' => $product->cost_price,
                    'discount_amount' => number_format((float) ($item['discount_amount'] ?? 0), 4, '.', ''),
                    'tax_amount' => number_format((float) ($item['tax_amount'] ?? 0), 4, '.', ''),
                    'tax_rate' => number_format((float) ($item['tax_rate'] ?? 0), 4, '.', ''),
                    'total_amount' => number_format((float) ($item['total_amount'] ?? 0), 4, '.', ''),
                ]);
            }

            foreach ($validated['payments'] as $payment) {
                SalePayment::query()->create([
                    'sale_id' => $saleModel->id,
                    'payment_method_id' => $payment['payment_method_id'],
                    'amount' => number_format((float) $payment['amount'], 4, '.', ''),
                    'reference' => $payment['reference'] ?? null,
                ]);
            }

            return $saleModel->load(['items', 'payments']);
        });

        return response()->json([
            'data' => $this->serializeSale($sale),
        ], 201);
    }

    /**
     * Serialize a sale for API responses.
     *
     * @return array<string, mixed>
     */
    protected function serializeSale(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'tenant_id' => $sale->tenant_id,
            'branch_id' => $sale->branch_id,
            'register_session_id' => $sale->register_session_id,
            'customer_id' => $sale->customer_id,
            'reference_no' => $sale->reference_no,
            'status' => $sale->status,
            'subtotal' => $sale->subtotal,
            'discount_amount' => $sale->discount_amount,
            'tax_amount' => $sale->tax_amount,
            'total_amount' => $sale->total_amount,
            'paid_amount' => $sale->paid_amount,
            'change_amount' => $sale->change_amount,
            'outstanding_amount' => $sale->outstanding_amount,
            'note' => $sale->note,
            'sold_by' => $sale->sold_by,
            'sold_at' => $sale->sold_at?->toIso8601String(),
            'items' => $sale->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'tax_amount' => $item->tax_amount,
                'tax_rate' => $item->tax_rate,
                'total_amount' => $item->total_amount,
            ])->values()->all(),
            'payments' => $sale->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_method_id' => $payment->payment_method_id,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
            ])->values()->all(),
        ];
    }
}
