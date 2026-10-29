<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BrandingUpdateRequest;
use App\Models\TenantBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantBrandingSettingsController extends Controller
{
    /**
     * Show the tenant branding settings page.
     */
    public function edit(Request $request): Response
    {
        $branding = $request->tenant->tenantBranding;

        return Inertia::render('settings/branding', [
            'branding' => $branding ?: new TenantBranding,
            'canEdit' => Auth::user()->can('update tenant branding'),
        ]);
    }

    /**
     * Update the tenant branding settings.
     */
    public function update(BrandingUpdateRequest $request): RedirectResponse
    {
        $branding = $request->tenant->tenantBranding ?? $request->tenant->tenantBranding()->firstOrCreate();

        $branding->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return to_route('tenant.branding');
    }

    /**
     * Delete the tenant branding.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $branding = $request->tenant->tenantBranding;

        if ($branding) {
            $branding->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding removed.')]);

        return to_route('tenant.branding');
    }
}
