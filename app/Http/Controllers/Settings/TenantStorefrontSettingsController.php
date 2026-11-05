<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\TenantSetting;
use App\Support\AuditService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantStorefrontSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $tenant = app(TenantContext::class)->getTenant();
        abort_if($tenant === null, 403);

        return Inertia::render('settings/tenant', [
            'currency' => $tenant->currency,
            'storefront' => [
                'is_storefront_enabled' => $tenant->tenantSetting?->is_storefront_enabled ?? true,
                'bank_transfer_instructions' => $tenant->tenantSetting?->bank_transfer_instructions,
                'storefront_description' => $tenant->tenantSetting?->storefront_description,
                'storefront_email' => $tenant->tenantSetting?->storefront_email,
                'storefront_phone' => $tenant->tenantSetting?->storefront_phone,
                'storefront_policy' => $tenant->tenantSetting?->storefront_policy,
                'storefront_pickup_enabled' => $tenant->tenantSetting?->storefront_pickup_enabled ?? true,
                'storefront_delivery_enabled' => $tenant->tenantSetting?->storefront_delivery_enabled ?? true,
                'paystack_enabled' => $tenant->tenantSetting?->paystack_enabled ?? false,
                'paystack_configured' => filled($tenant->tenantSetting?->paystack_secret),
            ],
            'branches' => $tenant->branches()
                ->where('is_active', true)
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get(['id', 'name', 'is_main', 'local_delivery_fee']),
            'canUpdate' => $request->user()?->can('settings.update') ?? false,
        ]);
    }

    public function update(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'is_storefront_enabled' => ['required', 'boolean'],
            'bank_transfer_instructions' => ['nullable', 'string', 'max:2000'],
            'storefront_description' => ['nullable', 'string', 'max:5000'],
            'storefront_email' => ['nullable', 'email', 'max:255'],
            'storefront_phone' => ['nullable', 'string', 'max:50'],
            'storefront_policy' => ['nullable', 'string', 'max:5000'],
            'storefront_pickup_enabled' => ['sometimes', 'boolean'],
            'storefront_delivery_enabled' => ['sometimes', 'boolean'],
            'paystack_enabled' => ['sometimes', 'boolean'],
            'paystack_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant = app(TenantContext::class)->getTenant();
        abort_if($tenant === null, 403);

        $settings = $tenant->tenantSetting()->firstOrCreate([]);
        $oldValues = $settings->getAttributes();
        $paystackSecret = $validated['paystack_secret'] ?? null;
        unset($validated['paystack_secret']);
        if ($paystackSecret !== null && $paystackSecret !== '') {
            $validated['paystack_secret'] = $paystackSecret;
        }
        $settings->update($validated);

        $secretWasSubmitted = array_key_exists('paystack_secret', $request->all())
            && filled($request->input('paystack_secret'));
        $auditValues = $validated;
        unset($auditValues['paystack_secret']);
        if ($secretWasSubmitted) {
            $auditValues['paystack_secret_updated'] = true;
        }

        $auditService->log(
            'storefront.settings.updated',
            TenantSetting::class,
            $settings->id,
            array_intersect_key($oldValues, $auditValues),
            $auditValues,
            tenant: $tenant,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Storefront settings updated.')]);

        return to_route('tenant.settings');
    }

    public function updateDeliveryFee(Request $request, Branch $branch, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'local_delivery_fee' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:99999999999.9999'],
        ]);

        $tenant = app(TenantContext::class)->getTenant();
        abort_if($tenant === null, 403);

        $branchRecord = $tenant->branches()
            ->where('is_active', true)
            ->whereKey($branch->id)
            ->firstOrFail();

        $oldValues = $branchRecord->getAttributes();
        $branchRecord->update($validated);

        $auditService->log(
            'storefront.delivery_fee.updated',
            Branch::class,
            $branchRecord->id,
            array_intersect_key($oldValues, $validated),
            $validated,
            tenant: $tenant,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Local delivery fee updated.')]);

        return to_route('tenant.settings');
    }
}
