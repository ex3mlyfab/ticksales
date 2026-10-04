<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display the active payment methods for the active tenant.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $paymentMethod) => [
                'id' => $paymentMethod->id,
                'tenant_id' => $paymentMethod->tenant_id,
                'name' => $paymentMethod->name,
                'type' => $paymentMethod->type,
                'is_active' => $paymentMethod->is_active,
                'sort_order' => $paymentMethod->sort_order,
            ]);

        return response()->json([
            'data' => $paymentMethods,
        ]);
    }
}
