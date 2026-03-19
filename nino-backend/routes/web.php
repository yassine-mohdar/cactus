<?php

use Illuminate\Support\Facades\Route;
use App\Modules\IAM\Http\Controllers\AuthController;
use App\Modules\IAM\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/', DashboardController::class)->name('admin.dashboard');

        // Impersonation routes
        Route::impersonate();

        // IAM / Staff
        Route::resource('staff', \App\Modules\IAM\Http\Controllers\StaffController::class)
            ->except(['show'])
            ->names('admin.staff');

        // Catalog
        Route::prefix('catalog')->name('admin.catalog.')->group(function () {
            Route::resource('categories', \App\Modules\Catalog\Http\Controllers\CategoryController::class)->except(['show']);
            Route::resource('products', \App\Modules\Catalog\Http\Controllers\ProductController::class)->except(['show']);
        });
    });
});

// Redirect root to admin
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});
