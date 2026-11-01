<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

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
