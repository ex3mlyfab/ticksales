<?php

use App\Http\Controllers\Web\SaleReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Sale Returns (refunds)
    Route::get('sale-returns', [SaleReturnController::class, 'index'])->name('sale-returns.index')->middleware('permission:sale-returns.view');
    Route::get('sale-returns/create', [SaleReturnController::class, 'create'])->name('sale-returns.create')->middleware('permission:sale-returns.create');
    Route::post('sale-returns', [SaleReturnController::class, 'store'])->name('sale-returns.store')->middleware('permission:sale-returns.create');
    Route::get('sale-returns/{saleReturn}', [SaleReturnController::class, 'show'])->name('sale-returns.show')->middleware('permission:sale-returns.view');
});
