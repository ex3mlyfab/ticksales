<?php

use App\Http\Controllers\Web\ProductBrandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('product-brands', [ProductBrandController::class, 'index'])->name('product-brands.index');
    Route::get('product-brands/create', [ProductBrandController::class, 'create'])->name('product-brands.create');
    Route::post('product-brands', [ProductBrandController::class, 'store'])->name('product-brands.store');
    Route::get('product-brands/{productBrand}/edit', [ProductBrandController::class, 'edit'])->name('product-brands.edit');
    Route::put('product-brands/{productBrand}', [ProductBrandController::class, 'update'])->name('product-brands.update');
    Route::delete('product-brands/{productBrand}', [ProductBrandController::class, 'destroy'])->name('product-brands.destroy');
});
