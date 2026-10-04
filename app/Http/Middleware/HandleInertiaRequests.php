<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = $request->attributes->get('tenant') ?? app(TenantContext::class)->getTenant();
        $branch = $this->resolveBranch($request, $tenant);
        $permissions = $request->user()?->permissions()?->pluck('name')->values()->all() ?? [];
        $branding = $this->resolveBranding($tenant);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'tenant' => $this->serializeTenant($tenant),
            'branch' => $this->serializeBranch($branch),
            'permissions' => $permissions,
            'branding' => $branding,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    protected function resolveBranch(Request $request, mixed $tenant): ?Branch
    {
        $branchId = $request->attributes->get('branch_id')
            ?? $request->session()->get('active_branch_id')
            ?? $request->input('branch_id')
            ?? $request->header('X-Branch-ID');

        if ($tenant === null || blank($branchId)) {
            return null;
        }

        return Branch::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($branchId)
            ->first();
    }

    protected function serializeTenant(mixed $tenant): ?array
    {
        if ($tenant === null) {
            return null;
        }

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'email' => $tenant->email,
            'currency' => $tenant->currency,
            'timezone' => $tenant->timezone,
            'locale' => $tenant->locale,
        ];
    }

    protected function serializeBranch(?Branch $branch): ?array
    {
        if ($branch === null) {
            return null;
        }

        return [
            'id' => $branch->id,
            'tenant_id' => $branch->tenant_id,
            'name' => $branch->name,
            'code' => $branch->code,
            'email' => $branch->email,
            'phone' => $branch->phone,
            'address' => $branch->address,
            'is_main' => (bool) $branch->is_main,
            'is_active' => (bool) $branch->is_active,
        ];
    }

    protected function resolveBranding(mixed $tenant): array
    {
        $branding = $tenant?->tenantBranding()->first();
        $name = config('app.name');

        if ($tenant !== null) {
            $name = $tenant->name;
        }

        return [
            'name' => $name,
            'logo_path' => $branding?->logo_path ?? null,
            'favicon_path' => $branding?->favicon_path ?? null,
            'primary_color' => $branding?->primary_color ?? '#111827',
            'secondary_color' => $branding?->secondary_color ?? '#f5f5f4',
            'accent_color' => $branding?->accent_color ?? '#0f172a',
            'sidebar_style' => $branding?->sidebar_style ?? 'default',
            'border_radius' => $branding?->border_radius ?? '0.625rem',
            'theme_preset' => $branding?->theme_preset ?? 'neutral',
        ];
    }
}
