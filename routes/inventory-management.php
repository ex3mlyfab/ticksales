<?php

use App\Http\Controllers\Web\StockAdjustmentController;
use App\Http\Controllers\Web\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Stock Adjustments
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::post('stock-adjustments/{stockAdjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post');
    Route::delete('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'destroy'])->name('stock-adjustments.destroy');

    // Stock Transfers
    Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
    Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::post('stock-transfers/{stockTransfer}/post', [StockTransferController::class, 'post'])->name('stock-transfers.post');
    Route::delete('stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');
});
