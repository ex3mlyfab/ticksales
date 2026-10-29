<?php

use App\Http\Controllers\Web\ProductUnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('product-units', [ProductUnitController::class, 'index'])->name('product-units.index');
    Route::get('product-units/create', [ProductUnitController::class, 'create'])->name('product-units.create');
    Route::post('product-units', [ProductUnitController::class, 'store'])->name('product-units.store');
    Route::get('product-units/{unit}/edit', [ProductUnitController::class, 'edit'])->name('product-units.edit');
    Route::put('product-units/{unit}', [ProductUnitController::class, 'update'])->name('product-units.update');
    Route::delete('product-units/{unit}', [ProductUnitController::class, 'destroy'])->name('product-units.destroy');
});
