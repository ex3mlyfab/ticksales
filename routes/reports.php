<?php

use App\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales')->middleware('permission:reports.view');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory')->middleware('permission:reports.view');
    Route::get('reports/register-sessions', [ReportController::class, 'registerSessions'])->name('reports.register-sessions')->middleware('permission:reports.view');
    Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses')->middleware('permission:reports.view');
});
