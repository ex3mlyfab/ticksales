<?php

use App\Http\Controllers\Web\SaleReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Sale Returns (refunds)
    Route::get('sale-returns', [SaleReturnController::class, 'index'])->name('sale-returns.index');
    Route::get('sale-returns/create', [SaleReturnController::class, 'create'])->name('sale-returns.create');
    Route::post('sale-returns', [SaleReturnController::class, 'store'])->name('sale-returns.store');
    Route::get('sale-returns/{saleReturn}', [SaleReturnController::class, 'show'])->name('sale-returns.show');
});
