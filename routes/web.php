<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\StorefrontCartController;
use App\Http\Controllers\Web\StorefrontCheckoutController;
use App\Http\Controllers\Web\StorefrontController;
use App\Http\Controllers\Web\StorefrontOrderManagementController;
use App\Http\Controllers\Web\StorefrontOrderTrackingController;
use App\Http\Controllers\Web\StorefrontPaystackController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/stores', [StorefrontController::class, 'directory'])->name('storefront.directory');
Route::get('/marketplace', [StorefrontController::class, 'marketplace'])->name('storefront.marketplace');
Route::get('/shop/{tenant:slug}', [StorefrontController::class, 'show'])->name('storefront.show');
Route::get('/shop/{tenant:slug}/products/{product}', [StorefrontController::class, 'product'])
    ->scopeBindings()
    ->name('storefront.products.show');
Route::get('/shop/{tenant:slug}/cart', [StorefrontCartController::class, 'show'])
    ->name('storefront.cart.show');
Route::post('/shop/{tenant:slug}/cart/items', [StorefrontCartController::class, 'add'])
    ->middleware('throttle:storefront-cart')
    ->name('storefront.cart.items.store');
Route::put('/shop/{tenant:slug}/cart/items/{product}', [StorefrontCartController::class, 'update'])
    ->whereUlid('product')
    ->middleware('throttle:storefront-cart')
    ->name('storefront.cart.items.update');
Route::delete('/shop/{tenant:slug}/cart/items/{product}', [StorefrontCartController::class, 'destroy'])
    ->whereUlid('product')
    ->middleware('throttle:storefront-cart')
    ->name('storefront.cart.items.destroy');
Route::get('/shop/{tenant:slug}/checkout', [StorefrontCheckoutController::class, 'show'])
    ->name('storefront.checkout.show');
Route::post('/shop/{tenant:slug}/checkout', [StorefrontCheckoutController::class, 'store'])
    ->middleware('throttle:storefront-checkout')
    ->name('storefront.checkout.store');
Route::get('/shop/{tenant:slug}/paystack/callback', [StorefrontPaystackController::class, 'callback'])
    ->name('storefront.paystack.callback');
Route::get('/shop/{tenant:slug}/orders/{storefrontOrder}', [StorefrontOrderTrackingController::class, 'show'])
    ->scopeBindings()
    ->middleware('throttle:storefront-tracking')
    ->name('storefront.orders.show');

Route::middleware(['auth', 'verified', 'tenant'])
    ->prefix('t/{tenant}')
    ->name('tenant.')
    ->scopeBindings()
    ->group(function () {
        Route::get('/storefront/orders', [StorefrontOrderManagementController::class, 'index'])
            ->middleware('permission:sales.view')
            ->name('storefront.orders.index');
        Route::post('/storefront/orders/{storefrontOrder}/accept', [StorefrontOrderManagementController::class, 'accept'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.accept');
        Route::post('/storefront/orders/{storefrontOrder}/ready', [StorefrontOrderManagementController::class, 'markReady'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.ready');
        Route::post('/storefront/orders/{storefrontOrder}/complete', [StorefrontOrderManagementController::class, 'complete'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.complete');
        Route::post('/storefront/orders/{storefrontOrder}/payment', [StorefrontOrderManagementController::class, 'confirmPayment'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.payment');
        Route::post('/storefront/orders/{storefrontOrder}/cancel', [StorefrontOrderManagementController::class, 'cancel'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.cancel');
        Route::patch('/storefront/orders/{storefrontOrder}/branch', [StorefrontOrderManagementController::class, 'reassignBranch'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.branch');
        Route::patch('/storefront/orders/{storefrontOrder}/delivery-tracking', [StorefrontOrderManagementController::class, 'updateDeliveryTracking'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.delivery-tracking');
        Route::post('/storefront/orders/{storefrontOrder}/erase-personal-data', [StorefrontOrderManagementController::class, 'eraseCustomerData'])
            ->middleware('permission:sales.update')
            ->name('storefront.orders.erase-personal-data');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/t/{tenant}/dashboard', [DashboardController::class, 'show'])->name('dashboard.tenant');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/product-categories.php';
require __DIR__.'/product-brands.php';
require __DIR__.'/product-units.php';
require __DIR__.'/tax-rates.php';
require __DIR__.'/discounts.php';
require __DIR__.'/products.php';
require __DIR__.'/inventory.php';
require __DIR__.'/inventory-management.php';
require __DIR__.'/customers.php';
require __DIR__.'/suppliers.php';
require __DIR__.'/purchases.php';
require __DIR__.'/pos.php';
require __DIR__.'/sale-returns.php';
require __DIR__.'/expenses.php';
require __DIR__.'/reports.php';
