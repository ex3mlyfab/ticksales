<?php

use App\Http\Controllers\Web\PaymentMethodController;
use App\Http\Controllers\Web\Pos\RegisterSessionController;
use App\Http\Controllers\Web\Pos\SaleController;
use App\Http\Controllers\Web\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Payment Methods
    Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::get('payment-methods/create', [PaymentMethodController::class, 'create'])->name('payment-methods.create');
    Route::post('payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::get('payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])->name('payment-methods.edit');
    Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

    // Registers
    Route::get('registers', [RegisterController::class, 'index'])->name('registers.index');
    Route::get('registers/create', [RegisterController::class, 'create'])->name('registers.create');
    Route::post('registers', [RegisterController::class, 'store'])->name('registers.store');
    Route::get('registers/{register}/edit', [RegisterController::class, 'edit'])->name('registers.edit');
    Route::put('registers/{register}', [RegisterController::class, 'update'])->name('registers.update');
    Route::delete('registers/{register}', [RegisterController::class, 'destroy'])->name('registers.destroy');

    // Register Sessions (open/close + cash movements)
    Route::get('register-sessions', [RegisterSessionController::class, 'index'])->name('register-sessions.index');
    Route::post('register-sessions', [RegisterSessionController::class, 'store'])->name('register-sessions.store');
    Route::post('register-sessions/{session}/close', [RegisterSessionController::class, 'close'])->name('register-sessions.close');
    Route::post('register-sessions/{session}/cash-movements', [RegisterSessionController::class, 'storeCashMovement'])->name('register-sessions.cash-movements.store');

    // POS Sale screen
    Route::get('pos', [SaleController::class, 'index'])->name('pos.index');
    Route::post('pos/sales', [SaleController::class, 'store'])->name('pos.sales.store');
    Route::get('pos/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('pos.sales.receipt');
});
