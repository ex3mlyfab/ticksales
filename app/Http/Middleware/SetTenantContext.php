<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        app(TenantContext::class)->setTenant($tenant);
        $request->attributes->set('tenant', $tenant);

        if ($tenant !== null) {
            $request->session()->put('active_tenant_id', $tenant->id);
        }

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        if ($user->is_platform_admin) {
            return null;
        }

        $tenantId = $request->session()->get('active_tenant_id')
            ?? $request->input('tenant_id')
            ?? $request->header('X-Tenant-ID');

        $tenantQuery = $user->tenants()
            ->wherePivot('is_active', true);

        if (filled($tenantId)) {
            $tenant = $tenantQuery->whereKey($tenantId)->first();

            if ($tenant !== null) {
                return $tenant;
            }
        }

        return $tenantQuery->first();
    }
}
