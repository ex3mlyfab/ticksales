<?php

use App\Http\Controllers\Web\ExpenseCategoryController;
use App\Http\Controllers\Web\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Expense Categories
    Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index')->middleware('permission:expense-categories.view');
    Route::get('expense-categories/create', [ExpenseCategoryController::class, 'create'])->name('expense-categories.create')->middleware('permission:expense-categories.create');
    Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store')->middleware('permission:expense-categories.create');
    Route::get('expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])->name('expense-categories.edit')->middleware('permission:expense-categories.update');
    Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update')->middleware('permission:expense-categories.update');
    Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy')->middleware('permission:expense-categories.delete');

    // Expenses
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('permission:expenses.view');
    Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create')->middleware('permission:expenses.create');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('permission:expenses.create');
    Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show')->middleware('permission:expenses.view');
    Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit')->middleware('permission:expenses.update');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update')->middleware('permission:expenses.update');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('permission:expenses.delete');
});
