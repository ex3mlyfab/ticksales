<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'register_id' => $this->register_id,
            'register_name' => $this->register?->name,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'status' => $this->status,
            'opening_balance' => (float) $this->opening_balance,
            'closing_balance' => $this->closing_balance !== null ? (float) $this->closing_balance : null,
            'expected_balance' => $this->expected_balance !== null ? (float) $this->expected_balance : null,
            'actual_balance' => $this->actual_balance !== null ? (float) $this->actual_balance : null,
            'variance' => $this->variance !== null ? (float) $this->variance : null,
            'opened_by_name' => $this->openedBy?->name,
            'closed_by_name' => $this->closedBy?->name,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
