<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'sessions_count' => $this->sessions()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
