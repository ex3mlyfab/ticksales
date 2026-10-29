<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
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
            'from_branch_id' => $this->from_branch_id,
            'to_branch_id' => $this->to_branch_id,
            'reference_no' => $this->reference_no,
            'status' => $this->status,
            'note' => $this->note,
            'transferred_by' => $this->transferred_by,
            'transferred_at' => $this->transferred_at?->toIso8601String(),
            'from_branch_name' => $this->fromBranch?->name,
            'to_branch_name' => $this->toBranch?->name,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'variant_id' => $item->variant_id,
                'quantity' => (float) $item->quantity,
                'unit_cost' => $item->unit_cost !== null ? (float) $item->unit_cost : null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
