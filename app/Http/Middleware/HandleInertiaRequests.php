<?php

namespace App\Http\Middleware;

use App\Models\ApplicationSetting;
use App\Models\Branch;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $permissions = $request->user()?->permissions($tenant)?->pluck('name')->values()->all() ?? [];
        $branding = $this->resolveBranding($tenant);
        $applicationSetting = ApplicationSetting::query()->first();
        $currency = $applicationSetting?->currency ?? 'NGN';

        return [
            ...parent::share($request),
            'name' => $applicationSetting?->app_name ?? config('app.name', 'Sokora'),
            'currency' => $currency,
            'auth' => [
                'user' => $request->user(),
            ],
            'tenant' => $this->serializeTenant($tenant),
            'tenants' => $this->serializeTenants($request),
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
        $applicationSetting = ApplicationSetting::query()->first();

        // A tenant's business name and logo lead within its own context;
        // the platform master brand applies everywhere else.
        $name = $tenant?->name
            ?? $applicationSetting?->app_name
            ?? config('app.name', 'Sokora');
        $logoPath = $tenant !== null
            ? ($branding?->logo_path ?? $applicationSetting?->logo_path)
            : $applicationSetting?->logo_path;

        return [
            'name' => $name,
            'logo_path' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'favicon_path' => $branding?->favicon_path,
            'accent_color' => $branding?->permittedAccent()?->value,
        ];
    }

    /**
     * List the user's active tenants so the UI can switch between them.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeTenants(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        $activeTenantId = app(TenantContext::class)->getTenant()?->id;

        return $user->tenants()
            ->wherePivot('is_active', true)
            ->orderBy('tenants.name')
            ->get()
            ->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
                'is_current' => $tenant->id === $activeTenantId,
            ])
            ->all();
    }
}
