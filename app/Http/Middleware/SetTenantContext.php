<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
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

        // Stateless (bearer token) API requests have no session; only persist
        // the active tenant for stateful requests that carry one.
        if ($tenant !== null && $request->hasSession()) {
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

        // An explicit tenant in the URL takes precedence so that every tenant
        // has its own dashboard (and future tenant-scoped routes) and the
        // request context is never ambiguous. The lookup stays scoped to the
        // user's own memberships, so a tenant bound from the URL that the user
        // does not belong to never becomes the active context.
        $routeTenant = $request->route('tenant');

        $routeTenantId = match (true) {
            $routeTenant instanceof Tenant => $routeTenant->id,
            is_string($routeTenant) => $routeTenant,
            default => null,
        };

        if ($routeTenantId !== null) {
            $tenant = $this->activeTenant($user, $routeTenantId);

            if ($tenant !== null) {
                return $tenant;
            }
        }

        $tenantId = ($request->hasSession() ? $request->session()->get('active_tenant_id') : null)
            ?? $request->input('tenant_id')
            ?? $request->header('X-Tenant-ID');

        if (filled($tenantId)) {
            $tenant = $this->activeTenant($user, $tenantId);

            if ($tenant !== null) {
                return $tenant;
            }
        }

        return $this->activeTenant($user);
    }

    /**
     * Resolve an active tenant the user belongs to.
     *
     * @param  string|null  $key  Restrict to a single tenant id.
     */
    protected function activeTenant(User $user, ?string $key = null): ?Tenant
    {
        $query = $user->tenants()->wherePivot('is_active', true);

        if ($key !== null) {
            $query->whereKey($key);
        }

        return $query->first();
    }
}
