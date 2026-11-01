<?php

use App\Http\Controllers\Web\ProductUnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('product-units', [ProductUnitController::class, 'index'])->name('product-units.index')->middleware('permission:product-units.view');
    Route::get('product-units/create', [ProductUnitController::class, 'create'])->name('product-units.create')->middleware('permission:product-units.create');
    Route::post('product-units', [ProductUnitController::class, 'store'])->name('product-units.store')->middleware('permission:product-units.create');
    Route::get('product-units/{productUnit}/edit', [ProductUnitController::class, 'edit'])->name('product-units.edit')->middleware('permission:product-units.update');
    Route::put('product-units/{productUnit}', [ProductUnitController::class, 'update'])->name('product-units.update')->middleware('permission:product-units.update');
    Route::delete('product-units/{productUnit}', [ProductUnitController::class, 'destroy'])->name('product-units.destroy')->middleware('permission:product-units.delete');
});
