use App\Models\Tenant;
use App\Models\User;

test('platform admins can update application branding and currency', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create(['currency' => 'USD']);

    $this->actingAs($admin)->post('/admin/branding', [
        'app_name' => 'My Market',
        'currency' => 'NGN',
    ])->assertRedirect('/admin/branding');

    $this->assertDatabaseHas('application_settings', [
        'app_name' => 'My Market',
        'currency' => 'NGN',
    ]);
    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'currency' => 'NGN',
    ]);
});

test('non platform admins cannot update application branding', function () {
    $user = User::factory()->create(['is_platform_admin' => false]);

    $this->actingAs($user)->post('/admin/branding', [
        'app_name' => 'Forbidden Brand',
        'currency' => 'NGN',
    ])->assertForbidden();
});

test('new tenants default to Nigerian Naira when currency is omitted', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->post('/admin/tenants', [
        'name' => 'Naira Store',
        'slug' => 'naira-store',
        'email' => 'store@example.test',
    ])->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'naira-store')->firstOrFail();

    expect($tenant->currency)->toBe('NGN');
});
