<?php

use App\Http\Controllers\Web\Admin\BrandingController;
use App\Http\Controllers\Web\Admin\PaystackPaymentController;
use App\Http\Controllers\Web\Admin\SubscriptionPaymentController;
use App\Http\Controllers\Web\Admin\SubscriptionPaymentMethodController;
use App\Http\Controllers\Web\Admin\SubscriptionPlanController;
use App\Http\Controllers\Web\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'platform_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('plans', SubscriptionPlanController::class)->except('show');
    Route::resource('subscription-payment-methods', SubscriptionPaymentMethodController::class)->except('show', 'destroy');
    Route::resource('tenants', TenantController::class);
    Route::get('branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::post('branding', [BrandingController::class, 'update'])->name('branding.update');
    Route::post('tenants/{tenant}/payments', [SubscriptionPaymentController::class, 'store'])->name('tenants.payments.store');
    Route::post('tenants/{tenant}/paystack-checkout', [PaystackPaymentController::class, 'store'])->name('tenants.paystack-checkout');
    Route::get('paystack/callback', [PaystackPaymentController::class, 'callback'])->name('paystack.callback');
});
