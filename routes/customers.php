<?php

use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\CustomerGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Customer Groups
    Route::get('customer-groups', [CustomerGroupController::class, 'index'])->name('customer-groups.index');
    Route::get('customer-groups/create', [CustomerGroupController::class, 'create'])->name('customer-groups.create');
    Route::post('customer-groups', [CustomerGroupController::class, 'store'])->name('customer-groups.store');
    Route::get('customer-groups/{group}/edit', [CustomerGroupController::class, 'edit'])->name('customer-groups.edit');
    Route::put('customer-groups/{group}', [CustomerGroupController::class, 'update'])->name('customer-groups.update');
    Route::delete('customer-groups/{group}', [CustomerGroupController::class, 'destroy'])->name('customer-groups.destroy');

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
});
