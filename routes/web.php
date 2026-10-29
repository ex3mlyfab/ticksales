<?php

use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
