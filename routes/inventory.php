<?php

use App\Http\Controllers\Web\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/product/{productId}', [InventoryController::class, 'show'])->name('inventory.show');
});
