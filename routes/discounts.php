<?php

use App\Http\Controllers\Web\DiscountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('discounts', [DiscountController::class, 'index'])->name('discounts.index')->middleware('permission:discounts.view');
    Route::get('discounts/create', [DiscountController::class, 'create'])->name('discounts.create')->middleware('permission:discounts.create');
    Route::post('discounts', [DiscountController::class, 'store'])->name('discounts.store')->middleware('permission:discounts.create');
    Route::get('discounts/{discount}/edit', [DiscountController::class, 'edit'])->name('discounts.edit')->middleware('permission:discounts.update');
    Route::put('discounts/{discount}', [DiscountController::class, 'update'])->name('discounts.update')->middleware('permission:discounts.update');
    Route::delete('discounts/{discount}', [DiscountController::class, 'destroy'])->name('discounts.destroy')->middleware('permission:discounts.delete');
});
