<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\KdsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ApiDocsController;

/*
|--------------------------------------------------------------------------
| BACKEND / ADMINISTRATOR ROUTES (/backend)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::prefix('backend')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected AdminLTE Routes (Requires Login)
    Route::middleware('auth')->group(function () {
        // Route /backend root -> redirect to dashboard
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Stores CRUD
        Route::resource('stores', StoreController::class);

        // Categories CRUD
        Route::resource('categories', CategoryController::class);

        // Menus & Options CRUD
        Route::resource('menus', MenuController::class);

        // Campaigns Open PO
        Route::resource('campaigns', CampaignController::class);

        // Orders Management
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

        // Kitchen Display System (KDS)
        Route::get('/kds', [KdsController::class, 'index'])->name('kds');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');

        // API Documentation
        Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('apiDocs');
    });
});

// Shortcuts & Aliases
Route::get('/admin', function () {
    return redirect('/backend');
});

Route::get('/admin/{any}', function ($any) {
    return redirect('/backend/' . $any);
})->where('any', '.*');

Route::get('/kds', function () {
    return redirect()->route('admin.kds');
})->name('kds.fullscreen');

use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| INERTIA VUE 3 FRONTEND ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/campaign/{token}', function ($token) {
    return Inertia::render('Campaign', [
        'token' => $token,
    ]);
})->name('campaign');

Route::get('/po/{token}', function ($token) {
    return redirect()->route('campaign', ['token' => $token]);
});

Route::get('/stores', function () {
    return Inertia::render('Stores');
})->name('stores');

Route::get('/trace/{orderNumber?}', function ($orderNumber = null) {
    return Inertia::render('Trace', [
        'orderNumber' => $orderNumber ?? '',
    ]);
})->name('trace');

