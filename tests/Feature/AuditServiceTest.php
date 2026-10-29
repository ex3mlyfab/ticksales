<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuditService;

test('audit service logs an event without HTTP context', function () {
    $service = app(AuditService::class);

    $log = $service->log(
        event: 'tenant.created',
        auditableType: Tenant::class,
        auditableId: '01m42ny1kp8qr5v9aawbxen3hy',
        newValues: ['name' => 'Acme']
    );

    expect($log)->toBeInstanceOf(AuditLog::class);
    expect($log->event)->toBe('tenant.created');
    expect($log->auditable_type)->toBe(Tenant::class);
    expect($log->auditable_id)->toBe('01m42ny1kp8qr5v9aawbxen3hy');
    expect($log->new_values)->toBe(['name' => 'Acme']);
    expect($log->tenant_id)->toBeNull();
    expect($log->user_id)->toBeNull();
});

test('audit service creates event logs with all context', function () {
    $service = app(AuditService::class);

    $log = $service->log(
        event: 'sale.created',
        auditableType: 'Sale',
        auditableId: '01m42ny1kp8qr5v9aawbxen3hy',
        oldValues: ['status' => 'pending'],
        newValues: ['status' => 'completed', 'total_amount' => '125.50'],
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Test Runner'
    );

    expect($log)->toBeInstanceOf(AuditLog::class);
    expect($log->event)->toBe('sale.created');
    expect($log->old_values)->toBe(['status' => 'pending']);
    expect($log->new_values)->toBe(['status' => 'completed', 'total_amount' => '125.50']);
    expect($log->ip_address)->toBe('127.0.0.1');
    expect($log->user_agent)->toBe('Pest Test Runner');
});

test('audit service created helper logs model creation with all attributes', function () {
    $service = app(AuditService::class);
    $tenant = Tenant::factory()->create();

    $log = $service->created($tenant, ['note' => 'First tenant']);

    expect($log)->toBeInstanceOf(AuditLog::class);
    expect($log->event)->toBe('tenant.created');
    expect($log->auditable_type)->toBe(Tenant::class);
    expect($log->auditable_id)->toBe($tenant->id);
    expect($log->new_values)->toHaveKey('name');
    expect($log->new_values)->toHaveKey('slug');
    expect($log->new_values['note'])->toBe('First tenant');
});

test('audit service updated helper logs model update with changed attributes', function () {
    $service = app(AuditService::class);
    $tenant = Tenant::factory()->create(['name' => 'Original Name']);

    $oldName = $tenant->name;

    $tenant->name = 'Updated Name';
    $tenant->save();

    $log = $service->updated($tenant, ['name' => 'Updated Name'], ['name' => $oldName]);

    expect($log)->toBeInstanceOf(AuditLog::class);
    expect($log->event)->toBe('tenant.updated');
    expect($log->auditable_type)->toBe(Tenant::class);
    expect($log->auditable_id)->toBe($tenant->id);
    expect($log->new_values)->toBe(['name' => 'Updated Name']);
    expect($log->old_values['name'])->toBe('Original Name');
});

test('audit service deleted helper logs model deletion with old attributes', function () {
    $service = app(AuditService::class);
    $tenant = Tenant::factory()->create(['name' => 'To Be Deleted']);

    $log = $service->deleted($tenant);

    expect($log)->toBeInstanceOf(AuditLog::class);
    expect($log->event)->toBe('tenant.deleted');
    expect($log->auditable_type)->toBe(Tenant::class);
    expect($log->auditable_id)->toBe($tenant->id);
    expect($log->old_values)->toHaveKey('name');
});

test('audit service stores events with correct schema', function () {
    $service = app(AuditService::class);

    $log = $service->log(
        event: 'user.updated',
        auditableType: User::class,
        auditableId: '01m42ny1kp8qr5v9aawbxen3hy',
        oldValues: ['name' => 'Old Name'],
        newValues: ['name' => 'New Name']
    );

    $log->refresh();

    expect($log->event)->toBe('user.updated');
    expect($log->auditable_type)->toBe(User::class);
    expect($log->old_values)->toBe(['name' => 'Old Name']);
    expect($log->new_values)->toBe(['name' => 'New Name']);
});
