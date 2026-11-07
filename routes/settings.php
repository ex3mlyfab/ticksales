<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\TenantBrandingSettingsController;
use App\Http\Controllers\Settings\TenantStorefrontSettingsController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
    Route::get('settings/tenant', [TenantStorefrontSettingsController::class, 'edit'])
        ->name('tenant.settings')
        ->middleware('permission:settings.view');
    Route::get(
        'settings/branding',
        [TenantBrandingSettingsController::class, 'edit'],
    )->name('tenant.branding')->middleware('permission:settings.view');

    Route::put('settings/tenant', [TenantStorefrontSettingsController::class, 'update'])
        ->name('tenant.settings.update')
        ->middleware('permission:settings.update');
    Route::put('settings/tenant/branches/{branch}/delivery-fee', [TenantStorefrontSettingsController::class, 'updateDeliveryFee'])
        ->name('tenant.settings.branches.delivery-fee.update')
        ->middleware('permission:settings.update');
    Route::post('settings/branding', [TenantBrandingSettingsController::class, 'update'])->name('tenant.branding.update')->middleware('permission:settings.update');
    Route::delete('settings/branding', [TenantBrandingSettingsController::class, 'destroy'])->name('tenant.branding.destroy')->middleware('permission:settings.update');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
