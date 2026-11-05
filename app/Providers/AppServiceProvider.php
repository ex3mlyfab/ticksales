<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Support\TenantContext;
use App\View\Components\InertiaApp;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureStorefrontRateLimiting();
        $this->configureInertiaShell();
    }

    protected function configureStorefrontRateLimiting(): void
    {
        $tenantThrottleKey = static function (Request $request): string {
            $tenant = $request->route('tenant');
            $tenantSlug = $tenant instanceof Tenant ? $tenant->slug : (string) $tenant;

            return $tenantSlug.'|'.$request->ip();
        };

        RateLimiter::for('storefront-cart', fn (Request $request): Limit => Limit::perMinute(30)
            ->by($tenantThrottleKey($request)));

        RateLimiter::for('storefront-checkout', fn (Request $request): Limit => Limit::perMinute(10)
            ->by($tenantThrottleKey($request)));

        RateLimiter::for('storefront-tracking', fn (Request $request): Limit => Limit::perMinute(30)
            ->by($request->ip()));
        RateLimiter::for('storefront-paystack-webhook', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($tenantThrottleKey($request)));
    }

    /**
     * Override the Inertia app shell component so the inline page-data script
     * carries the Content-Security-Policy nonce (strict CSP support).
     */
    protected function configureInertiaShell(): void
    {
        Blade::component(InertiaApp::class, 'inertia::app');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
