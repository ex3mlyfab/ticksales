<?php

use App\Http\Controllers\Web\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'platform_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('tenants', TenantController::class);
});
