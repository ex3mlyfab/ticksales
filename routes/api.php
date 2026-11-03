<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductBrandController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Web\Admin\PaystackPaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/paystack/webhook', [PaystackPaymentController::class, 'webhook'])->name('paystack.webhook');

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view');
        Route::get('/products/search', [ProductController::class, 'search'])->middleware('permission:products.view');
        Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view');
        Route::get('/product-categories', [ProductCategoryController::class, 'index'])->middleware('permission:product-categories.view');
        Route::get('/product-brands', [ProductBrandController::class, 'index'])->middleware('permission:product-brands.view');
        Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view');
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view');
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.create');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.update');
        Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.view');
        Route::post('/sales', [SaleController::class, 'store'])->middleware('permission:sales.create');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view');
        Route::get('/registers', [RegisterController::class, 'index'])->middleware('permission:registers.view');
        Route::get('/registers/{register}/session', [RegisterController::class, 'session'])->middleware('permission:registers.view');
        Route::post('/registers/{register}/open', [RegisterController::class, 'open'])->middleware('permission:registers.open');
        Route::post('/registers/{register}/close', [RegisterController::class, 'close'])->middleware('permission:registers.close');
        Route::post('/registers/{register}/cash-movement', [RegisterController::class, 'cashMovement'])->middleware('permission:registers.open');
        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->middleware('permission:payment-methods.view');
        Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary'])->middleware('permission:reports.view');
        Route::get('/reports/register-session/{session}', [ReportController::class, 'registerSession'])->middleware('permission:reports.view');
    });
});
