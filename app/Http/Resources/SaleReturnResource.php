<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'sale_reference' => $this->sale?->reference_no,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'reference_no' => $this->reference_no,
            'reason' => $this->reason,
            'refund_amount' => (float) $this->refund_amount,
            'refund_method_id' => $this->refund_method_id,
            'refund_method_name' => $this->refundMethod?->name,
            'status' => $this->status,
            'returned_by_name' => $this->returnedBy?->name,
            'returned_at' => $this->returned_at?->toIso8601String(),
            'customer_name' => $this->sale?->customer?->name,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'variant_id' => $item->variant_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'tax_amount' => (float) $item->tax_amount,
                'line_total' => (float) $item->line_total,
            ])),
        ];
    }
}
