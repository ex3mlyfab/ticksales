<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BrandingUpdateRequest;
use App\Models\Tenant;
use App\Support\TenantContext;
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
        $branding = $this->tenant($request)->tenantBranding;

        return Inertia::render('settings/branding', [
            'branding' => [
                'name' => $tenant->name,
                'logo_path' => $branding?->logo_path,
                'favicon_path' => $branding?->favicon_path,
                'accent_color' => $branding?->permittedAccent()?->value,
            ],
            'canEdit' => Auth::user()->can('settings.update'),
        ]);
    }

    /**
     * Update the tenant branding settings.
     */
    public function update(BrandingUpdateRequest $request): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $branding = $tenant->tenantBranding ?? $tenant->tenantBranding()->firstOrCreate();

        $branding->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return to_route('tenant.branding');
    }

    /**
     * Delete the tenant branding.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $branding = $this->tenant($request)->tenantBranding;

        if ($branding) {
            $branding->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding removed.')]);

        return to_route('tenant.branding');
    }

    /**
     * The tenant bound to the request by SetTenantContext.
     */
    private function tenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('tenant') ?? app(TenantContext::class)->getTenant();

        abort_if($tenant === null, 403);

        return $tenant;
    }
}
