<?php

namespace App\Concerns;

use Illuminate\Support\Carbon;

/**
 * Trait for managing tenant subscription state.
 *
 * @property string|null $plan
 * @property Carbon|null $trial_ends_at
 * @property string|null $status
 */
trait HasSubscription
{
    /**
     * Get the current subscription status.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->plan) {
            return 'inactive';
        }

        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return 'trialing';
        }

        return 'active';
    }

    /**
     * Determine if the tenant is currently trialing.
     */
    public function isTrialing(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the tenant has an active subscription.
     */
    public function isActive(): bool
    {
        return (bool) $this->plan && ! $this->isTrialing();
    }

    /**
     * Check if the tenant has access to a specific feature based on their plan.
     */
    public function can(string $feature): bool
    {
        $planFeatures = [
            'basic' => ['pos', 'products', 'customers', 'inventory_basic'],
            'pro' => ['pos', 'products', 'customers', 'inventory_advanced', 'reports', 'multi_branch'],
            'enterprise' => ['pos', 'products', 'customers', 'inventory_advanced', 'reports', 'multi_branch', 'custom_branding', 'priority_support'],
        ];

        $plan = $this->plan ?? 'basic';

        return isset($planFeatures[$plan]) && in_array($feature, $planFeatures[$plan], true);
    }

    /**
     * Get the maximum number of branches allowed for this plan.
     */
    public function maxBranches(): int
    {
        return match ($this->plan) {
            'basic' => 1,
            'pro' => 10,
            'enterprise' => 9999,
            default => 1,
        };
    }
}
