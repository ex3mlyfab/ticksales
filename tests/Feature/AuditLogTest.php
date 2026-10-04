<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;

test('audit log records can be created for a tenant action', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $log = AuditLog::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'event' => 'sale.created',
        'auditable_type' => 'sale',
        'auditable_id' => '01m42ny1kp8qr5v9aawbxen3hy',
        'old_values' => ['amount' => 0],
        'new_values' => ['amount' => 125.50],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Test Runner',
    ]);

    expect($log->tenant_id)->toBe($tenant->id)
        ->and($log->event)->toBe('sale.created')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->old_values)->toMatchArray(['amount' => 0])
        ->and($log->new_values)->toMatchArray(['amount' => 125.5]);
});
