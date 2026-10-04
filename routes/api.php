<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('web')->post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['web', 'auth', 'tenant'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::get('/product-categories', [ProductCategoryController::class, 'index']);
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);
        Route::get('/registers', [RegisterController::class, 'index']);
        Route::get('/registers/{register}/session', [RegisterController::class, 'session']);
        Route::post('/registers/{register}/open', [RegisterController::class, 'open']);
        Route::post('/registers/{register}/close', [RegisterController::class, 'close']);
        Route::post('/registers/{register}/cash-movement', [RegisterController::class, 'cashMovement']);
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);
        Route::get('/reports/register-session/{session}', [ReportController::class, 'registerSession']);
    });
});
