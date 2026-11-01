<?php

use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\PurchaseOrderController;
use App\Http\Controllers\Web\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Purchase Orders
    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index')->middleware('permission:purchase-orders.view');
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create')->middleware('permission:purchase-orders.create');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store')->middleware('permission:purchase-orders.create');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show')->middleware('permission:purchase-orders.view');
    Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit')->middleware('permission:purchase-orders.update');
    Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update')->middleware('permission:purchase-orders.update');
    Route::post('purchase-orders/{purchaseOrder}/post', [PurchaseOrderController::class, 'post'])->name('purchase-orders.post')->middleware('permission:purchases.create');
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy')->middleware('permission:purchase-orders.delete');

    // Purchases
    Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index')->middleware('permission:purchases.view');
    Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create')->middleware('permission:purchases.create');
    Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store')->middleware('permission:purchases.create');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show')->middleware('permission:purchases.view');

    // Purchase Returns
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index')->middleware('permission:purchase-returns.view');
    Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create')->middleware('permission:purchase-returns.create');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store')->middleware('permission:purchase-returns.create');
    Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show')->middleware('permission:purchase-returns.view');
    Route::get('purchase-returns/{purchaseReturn}/edit', [PurchaseReturnController::class, 'edit'])->name('purchase-returns.edit')->middleware('permission:purchase-returns.update');
    Route::put('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'update'])->name('purchase-returns.update')->middleware('permission:purchase-returns.update');
    Route::post('purchase-returns/{purchaseReturn}/post', [PurchaseReturnController::class, 'post'])->name('purchase-returns.post')->middleware('permission:purchases.update');
    Route::delete('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->name('purchase-returns.destroy')->middleware('permission:purchase-returns.delete');
});
