<?php

use App\Http\Controllers\Web\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index')->middleware('permission:product-categories.view');
    Route::get('product-categories/create', [ProductCategoryController::class, 'create'])->name('product-categories.create')->middleware('permission:product-categories.create');
    Route::post('product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store')->middleware('permission:product-categories.create');
    Route::get('product-categories/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->name('product-categories.edit')->middleware('permission:product-categories.update');
    Route::put('product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('product-categories.update')->middleware('permission:product-categories.update');
    Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy')->middleware('permission:product-categories.delete');
});
