<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Service for creating audit log entries with automatic context detection.
 *
 * The AuditService simplifies audit logging by automatically capturing
 * tenant, branch, and user context from the current HTTP request or
 * authenticated user when available.
 */
class AuditService
{
    /**
     * Create a new audit log entry.
     *
     * @param  string  $event  The event name (e.g., 'sale.created', 'user.updated')
     * @param  string|null  $auditableType  The model class name of the auditable model
     * @param  string|null  $auditableId  The ULID of the auditable model
     * @param  array|null  $oldValues  The previous state of the auditable model
     * @param  array|null  $newValues  The new state of the auditable model
     * @param  string|null  $ipAddress  The IP address of the request
     * @param  string|null  $userAgent  The user agent of the request
     */
    public function log(
        string $event,
        ?string $auditableType = null,
        ?string $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        // Get tenant from request attributes (set by SetTenantContext middleware)
        $tenant = $this->currentTenant();

        // Get branch from session or request
        $branchId = $this->currentBranchId($tenant);

        // Get user from request (authenticated user)
        $user = $this->currentUser();

        // Resolve IP address if not provided
        if (! $ipAddress) {
            $ipAddress = $this->currentIpAddress();
        }

        // Resolve user agent if not provided
        if (! $userAgent) {
            $userAgent = $this->currentUserAgent();
        }

        return AuditLog::query()->create([
            'tenant_id' => $tenant ? $tenant->id : null,
            'branch_id' => $branchId,
            'user_id' => $user ? $user->id : null,
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get the current tenant, either from request attributes or from the user's first active tenant.
     */
    protected function currentTenant(): ?Tenant
    {
        $request = $this->currentRequest();
        if (! $request) {
            return null;
        }

        // Try request attributes first (set by SetTenantContext middleware)
        $tenant = $request->attributes->get('tenant');
        if ($tenant !== null) {
            return $tenant;
        }

        // Fallback: get from user's tenants
        $user = $request->user();
        if ($user) {
            return $user->tenants()->wherePivot('is_active', true)->first();
        }

        return null;
    }

    /**
     * Get the current branch ID from the request session or query parameters.
     */
    protected function currentBranchId(?Tenant $tenant): ?string
    {
        $request = $this->currentRequest();
        if (! $request) {
            return null;
        }

        $branchId = null;

        // Try session
        if ($request->hasSession()) {
            $branchId = $request->session()->get('active_branch_id');
        }

        // Try request input
        if (! $branchId) {
            $branchId = $request->input('branch_id');
        }

        // Try query parameter
        if (! $branchId) {
            $branchId = $request->query('branch_id');
        }

        return $branchId;
    }

    /**
     * Get the currently authenticated user.
     */
    protected function currentUser(): ?User
    {
        $request = $this->currentRequest();
        if (! $request) {
            return null;
        }

        return $request->user();
    }

    /**
     * Get the IP address of the current request.
     */
    protected function currentIpAddress(): ?string
    {
        $request = $this->currentRequest();
        if (! $request) {
            return null;
        }

        return $request->ip();
    }

    /**
     * Get the user agent of the current request.
     */
    protected function currentUserAgent(): ?string
    {
        $request = $this->currentRequest();
        if (! $request) {
            return null;
        }

        return $request->header('User-Agent');
    }

    /**
     * Get the current HTTP request instance, or null if outside a request.
     */
    protected function currentRequest(): ?Request
    {
        try {
            return app('request');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Log a model creation event.
     *
     * @param  Model  $model
     * @param  array|null  $additionalData  Additional data to include in new_values
     */
    public function created($model, ?array $additionalData = null): AuditLog
    {
        $newValues = $model->getAttributes();
        if ($additionalData) {
            $newValues = array_merge($newValues, $additionalData);
        }

        return $this->log(
            event: sprintf('%s.created', strtolower(class_basename($model))),
            auditableType: get_class($model),
            auditableId: $model->id,
            newValues: $newValues
        );
    }

    /**
     * Log a model update event.
     *
     * @param  Model  $model
     * @param  array  $changes  The changed attributes and their new values
     * @param  array|null  $oldValues  The previous state of changed attributes
     */
    public function updated($model, array $changes, ?array $oldValues = null): AuditLog
    {
        // If old values not provided, get current model attributes for changed keys
        if (! $oldValues) {
            $oldValues = [];
            foreach ($changes as $key => $value) {
                if ($model->getOriginal($key) !== null) {
                    $oldValues[$key] = $model->getOriginal($key);
                }
            }
        }

        $newValues = [];
        foreach ($changes as $key => $value) {
            $newValues[$key] = $value;
        }

        return $this->log(
            event: sprintf('%s.updated', strtolower(class_basename($model))),
            auditableType: get_class($model),
            auditableId: $model->id,
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    /**
     * Log a model deletion event.
     *
     * @param  Model  $model
     */
    public function deleted($model): AuditLog
    {
        return $this->log(
            event: sprintf('%s.deleted', strtolower(class_basename($model))),
            auditableType: get_class($model),
            auditableId: $model->id,
            oldValues: $model->getAttributes()
        );
    }
}
