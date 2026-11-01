<?php

use App\Http\Controllers\Web\ProductBrandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('product-brands', [ProductBrandController::class, 'index'])->name('product-brands.index')->middleware('permission:product-brands.view');
    Route::get('product-brands/create', [ProductBrandController::class, 'create'])->name('product-brands.create')->middleware('permission:product-brands.create');
    Route::post('product-brands', [ProductBrandController::class, 'store'])->name('product-brands.store')->middleware('permission:product-brands.create');
    Route::get('product-brands/{productBrand}/edit', [ProductBrandController::class, 'edit'])->name('product-brands.edit')->middleware('permission:product-brands.update');
    Route::put('product-brands/{productBrand}', [ProductBrandController::class, 'update'])->name('product-brands.update')->middleware('permission:product-brands.update');
    Route::delete('product-brands/{productBrand}', [ProductBrandController::class, 'destroy'])->name('product-brands.destroy')->middleware('permission:product-brands.delete');
});
