<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Scopes foreign-key validation rules to the active tenant.
 *
 * Plain `exists:` rules only assert that a row exists anywhere in the table,
 * which lets a client attach a record owned by another tenant. Every
 * tenant-owned foreign key validated by a form request should use
 * {@see self::existsInTenant()} instead so the lookup is bounded to the
 * tenant resolved by {@see SetTenantContext}.
 */
trait ScopesValidationToTenant
{
    /**
     * The id of the tenant this request is operating within.
     */
    protected function tenantId(): ?string
    {
        return $this->attributes->get('tenant')?->id;
    }

    /**
     * A tenant-scoped exists rule for the given table.
     */
    protected function existsInTenant(string $table, ?string $tenantId = null): Exists
    {
        return Rule::exists($table, 'id')->where(
            fn ($query) => $query->where('tenant_id', $tenantId ?? $this->tenantId()),
        );
    }
}
