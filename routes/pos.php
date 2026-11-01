<?php

use App\Http\Controllers\Web\PaymentMethodController;
use App\Http\Controllers\Web\Pos\RegisterSessionController;
use App\Http\Controllers\Web\Pos\SaleController;
use App\Http\Controllers\Web\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Payment Methods
    Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index')->middleware('permission:payment-methods.view');
    Route::get('payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment-methods.create')->middleware('permission:payment-methods.create');
    Route::post('payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store')->middleware('permission:payment-methods.create');
    Route::get('payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])->name('payment-methods.edit')->middleware('permission:payment-methods.update');
    Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update')->middleware('permission:payment-methods.update');
    Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy')->middleware('permission:payment-methods.delete');

    // Registers
    Route::get('registers', [RegisterController::class, 'index'])->name('registers.index')->middleware('permission:registers.view');
    Route::get('registers/create', [RegisterController::class, 'create'])->name('registers.create')->middleware('permission:registers.create');
    Route::post('registers', [RegisterController::class, 'store'])->name('registers.store')->middleware('permission:registers.create');
    Route::get('registers/{register}/edit', [RegisterController::class, 'edit'])->name('registers.edit')->middleware('permission:registers.update');
    Route::put('registers/{register}', [RegisterController::class, 'update'])->name('registers.update')->middleware('permission:registers.update');
    Route::delete('registers/{register}', [RegisterController::class, 'destroy'])->name('registers.destroy')->middleware('permission:registers.delete');

    // Register Sessions (open/close + cash movements)
    Route::get('register-sessions', [RegisterSessionController::class, 'index'])->name('register-sessions.index')->middleware('permission:registers.view');
    Route::post('register-sessions', [RegisterSessionController::class, 'store'])->name('register-sessions.store')->middleware('permission:registers.open');
    Route::post('register-sessions/{session}/close', [RegisterSessionController::class, 'close'])->name('register-sessions.close')->middleware('permission:registers.close');
    Route::post('register-sessions/{session}/cash-movements', [RegisterSessionController::class, 'storeCashMovement'])->name('register-sessions.cash-movements.store')->middleware('permission:registers.open');

    // POS Sale screen
    Route::get('pos', [SaleController::class, 'index'])->name('pos.index')->middleware('permission:sales.view');
    Route::post('pos/sales', [SaleController::class, 'store'])->name('pos.sales.store')->middleware('permission:sales.create');
    Route::get('pos/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('pos.sales.receipt')->middleware('permission:sales.view');
});
