<?php

use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\PurchaseOrderController;
use App\Http\Controllers\Web\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Purchase Orders
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
    Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    Route::post('purchase-orders/{purchaseOrder}/post', [PurchaseOrderController::class, 'post'])->name('purchase-orders.post');
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

    // Purchases
    Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');

    // Purchase Returns
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
    Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
    Route::get('purchase-returns/{purchaseReturn}/edit', [PurchaseReturnController::class, 'edit'])->name('purchase-returns.edit');
    Route::put('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'update'])->name('purchase-returns.update');
    Route::post('purchase-returns/{purchaseReturn}/post', [PurchaseReturnController::class, 'post'])->name('purchase-returns.post');
    Route::delete('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->name('purchase-returns.destroy');
});
