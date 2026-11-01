<?php

use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\CustomerGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Customer Groups
    Route::get('customer-groups', [CustomerGroupController::class, 'index'])->name('customer-groups.index')->middleware('permission:customer-groups.view');
    Route::get('customer-groups/create', [CustomerGroupController::class, 'create'])->name('customer-groups.create')->middleware('permission:customer-groups.create');
    Route::post('customer-groups', [CustomerGroupController::class, 'store'])->name('customer-groups.store')->middleware('permission:customer-groups.create');
    Route::get('customer-groups/{customerGroup}/edit', [CustomerGroupController::class, 'edit'])->name('customer-groups.edit')->middleware('permission:customer-groups.update');
    Route::put('customer-groups/{customerGroup}', [CustomerGroupController::class, 'update'])->name('customer-groups.update')->middleware('permission:customer-groups.update');
    Route::delete('customer-groups/{customerGroup}', [CustomerGroupController::class, 'destroy'])->name('customer-groups.destroy')->middleware('permission:customer-groups.delete');

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index')->middleware('permission:customers.view');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create')->middleware('permission:customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store')->middleware('permission:customers.create');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit')->middleware('permission:customers.update');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')->middleware('permission:customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy')->middleware('permission:customers.delete');
});
