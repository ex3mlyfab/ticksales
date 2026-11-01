<?php

use App\Http\Controllers\Web\StockAdjustmentController;
use App\Http\Controllers\Web\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Stock Adjustments
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index')->middleware('permission:stock-adjustments.view');
    Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create')->middleware('permission:stock-adjustments.create');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store')->middleware('permission:stock-adjustments.create');
    Route::post('stock-adjustments/{stockAdjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post')->middleware('permission:inventory.adjust');
    Route::delete('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'destroy'])->name('stock-adjustments.destroy')->middleware('permission:stock-adjustments.delete');

    // Stock Transfers
    Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index')->middleware('permission:stock-transfers.view');
    Route::get('stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create')->middleware('permission:stock-transfers.create');
    Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store')->middleware('permission:stock-transfers.create');
    Route::post('stock-transfers/{stockTransfer}/post', [StockTransferController::class, 'post'])->name('stock-transfers.post')->middleware('permission:inventory.transfer');
    Route::delete('stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy')->middleware('permission:stock-transfers.delete');
});
