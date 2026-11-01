<?php

use App\Http\Controllers\Web\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index')->middleware('permission:tax-rates.view');
    Route::get('tax-rates/create', [TaxRateController::class, 'create'])->name('tax-rates.create')->middleware('permission:tax-rates.create');
    Route::post('tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store')->middleware('permission:tax-rates.create');
    Route::get('tax-rates/{taxRate}/edit', [TaxRateController::class, 'edit'])->name('tax-rates.edit')->middleware('permission:tax-rates.update');
    Route::put('tax-rates/{taxRate}', [TaxRateController::class, 'update'])->name('tax-rates.update')->middleware('permission:tax-rates.update');
    Route::delete('tax-rates/{taxRate}', [TaxRateController::class, 'destroy'])->name('tax-rates.destroy')->middleware('permission:tax-rates.delete');
});
