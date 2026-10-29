<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'reference_no' => $this->reference_no,
            'reason' => $this->reason,
            'status' => $this->status,
            'adjusted_by' => $this->adjusted_by,
            'adjusted_at' => $this->adjusted_at?->toIso8601String(),
            'branch_name' => $this->branch?->name,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'variant_id' => $item->variant_id,
                'quantity_before' => (float) $item->quantity_before,
                'quantity_after' => (float) $item->quantity_after,
                'quantity_adjusted' => (float) $item->quantity_adjusted,
                'unit_cost' => $item->unit_cost !== null ? (float) $item->unit_cost : null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
