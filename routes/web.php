<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Super Admin Controllers
use App\Http\Controllers\SuperAdmin\SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminStoreController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;

// Store Backend Controllers
use App\Http\Controllers\StoreBackend\StoreBackendAuthController;
use App\Http\Controllers\StoreBackend\StoreBackendDashboardController;
use App\Http\Controllers\StoreBackend\StoreBackendMenuController;
use App\Http\Controllers\StoreBackend\StoreBackendCategoryController;
use App\Http\Controllers\StoreBackend\StoreBackendCampaignController;
use App\Http\Controllers\StoreBackend\StoreBackendOrderController;
use App\Http\Controllers\StoreBackend\StoreBackendKdsController;
use App\Http\Controllers\StoreBackend\StoreBackendReportController;
use App\Http\Controllers\StoreBackend\StoreBackendSettingsController;
use App\Http\Controllers\StoreBackend\StoreBackendLoyaltyCustomerController;
use App\Http\Controllers\StoreBackend\StoreBackendLoyaltyPromoController;

// Public Front Controller
use App\Http\Controllers\StoreFrontController;

// Legacy Admin Controllers (for /backend compatibility)
use App\Http\Controllers\Admin\AuthController as LegacyAuthController;
use App\Http\Controllers\Admin\DashboardController as LegacyDashboardController;
use App\Http\Controllers\Admin\StoreController as LegacyStoreController;
use App\Http\Controllers\Admin\CategoryController as LegacyCategoryController;
use App\Http\Controllers\Admin\MenuController as LegacyMenuController;
use App\Http\Controllers\Admin\CampaignController as LegacyCampaignController;
use App\Http\Controllers\Admin\OrderController as LegacyOrderController;
use App\Http\Controllers\Admin\KdsController as LegacyKdsController;
use App\Http\Controllers\Admin\ReportController as LegacyReportController;
use App\Http\Controllers\Admin\ApiDocsController as LegacyApiDocsController;

/*
|--------------------------------------------------------------------------
| 1. SUPER ADMIN PORTAL (/superadmin)
| Kredensial: Username: naufal_su | Password: naufal_super2020
|--------------------------------------------------------------------------
*/
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/login', [SuperAdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [SuperAdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [SuperAdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['superadmin'])->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard.index');

        // Manage Stores (CRUD & URLs)
        Route::resource('stores', SuperAdminStoreController::class);

        // Manage Users per Toko
        Route::resource('users', SuperAdminUserController::class);

        // Fast switch to store backend
        Route::get('/switch-store/{store}', [SuperAdminDashboardController::class, 'switchToStore'])->name('switchStore');
    });
});

/*
|--------------------------------------------------------------------------
| 2. STORE BACKEND PER TOKO (/{store_slug}/backend)
| Mengelola data toko masing-masing (Menu, Kategori, PO, Order, KDS, Laporan, Setting)
|--------------------------------------------------------------------------
*/
Route::prefix('{store_slug}/backend')->name('store.backend.')->group(function () {
    Route::get('/login', [StoreBackendAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StoreBackendAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [StoreBackendAuthController::class, 'logout'])->name('logout');

    Route::middleware(['store.scope'])->group(function () {
        Route::get('/', [StoreBackendDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [StoreBackendDashboardController::class, 'index'])->name('dashboard.index');

        // Menus CRUD
        Route::resource('menus', StoreBackendMenuController::class);

        // Categories CRUD
        Route::resource('categories', StoreBackendCategoryController::class);

        // Campaigns Open PO CRUD
        Route::resource('campaigns', StoreBackendCampaignController::class);

        // Orders Management
        Route::get('/orders', [StoreBackendOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [StoreBackendOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [StoreBackendOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::post('/orders/{order}/sync-mayar', [StoreBackendOrderController::class, 'syncMayarPayment'])->name('orders.syncMayar');

        // Kitchen Display System (KDS)
        Route::get('/kds', [StoreBackendKdsController::class, 'index'])->name('kds');

        // Reports
        Route::get('/reports', [StoreBackendReportController::class, 'index'])->name('reports');

        // Loyalty Customers Management
        Route::get('/loyalty-customers', [StoreBackendLoyaltyCustomerController::class, 'index'])->name('loyaltyCustomers.index');
        Route::get('/loyalty-customers/{customer}', [StoreBackendLoyaltyCustomerController::class, 'show'])->name('loyaltyCustomers.show');
        Route::post('/loyalty-customers/{customer}/adjust-points', [StoreBackendLoyaltyCustomerController::class, 'adjustPoints'])->name('loyaltyCustomers.adjustPoints');

        // Loyalty Promos Management
        Route::resource('loyalty-promos', StoreBackendLoyaltyPromoController::class)->names('loyaltyPromos');

        // Store Profile, QRIS & Payment Gateway Settings
        Route::get('/settings', [StoreBackendSettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [StoreBackendSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-mayar', [StoreBackendSettingsController::class, 'testMayar'])->name('settings.testMayar');
    });
});

/*
|--------------------------------------------------------------------------
| 3. LEGACY / GLOBAL BACKEND ROUTES (/backend)
|--------------------------------------------------------------------------
*/
Route::prefix('backend')->name('admin.')->group(function () {
    Route::get('/login', [LegacyAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LegacyAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [LegacyAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', function () {
            if (auth()->check() && auth()->user()->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }
            if (auth()->check() && auth()->user()->store) {
                return redirect()->route('store.backend.dashboard', auth()->user()->store->slug);
            }
            return redirect()->route('admin.dashboard');
        });

        Route::get('/dashboard', [LegacyDashboardController::class, 'index'])->name('dashboard');
        Route::resource('stores', LegacyStoreController::class);
        Route::resource('categories', LegacyCategoryController::class);
        Route::resource('menus', LegacyMenuController::class);
        Route::resource('campaigns', LegacyCampaignController::class);
        Route::get('/orders', [LegacyOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [LegacyOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [LegacyOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::get('/kds', [LegacyKdsController::class, 'index'])->name('kds');
        Route::get('/reports', [LegacyReportController::class, 'index'])->name('reports');
        Route::get('/api-docs', [LegacyApiDocsController::class, 'index'])->name('apiDocs');
    });
});

Route::get('/admin', function () {
    return redirect('/superadmin');
});

Route::get('/admin/{any}', function ($any) {
    return redirect('/backend/' . $any);
})->where('any', '.*');

Route::get('/kds', function () {
    return redirect()->route('admin.kds');
})->name('kds.fullscreen');

/*
|--------------------------------------------------------------------------
| 4. PUBLIC FRONTEND ROUTES (INERTIA VUE 3)
|--------------------------------------------------------------------------
*/

// Root URL (/) - Directory / Looping Per Toko
Route::get('/', [StoreFrontController::class, 'directory'])->name('home');

Route::get('/stores', function () {
    return redirect()->route('home');
})->name('stores');

Route::get('/campaign/{token}', function ($token) {
    return Inertia::render('Campaign', [
        'token' => $token,
    ]);
})->name('campaign');

Route::get('/po/{token}', function ($token) {
    return redirect()->route('campaign', ['token' => $token]);
});

Route::get('/trace/{orderNumber?}', function ($orderNumber = null) {
    return Inertia::render('Trace', [
        'orderNumber' => $orderNumber ?? '',
    ]);
})->name('trace');

// Customer Account Activation Link
Route::get('/customer/activate/{token}', [\App\Http\Controllers\CustomerAuthController::class, 'activate'])->name('customer.activate');

// Customer Loyalty Profile Page (Frontend)
Route::get('/customer/profile', [\App\Http\Controllers\CustomerAuthController::class, 'profileView'])->name('customer.profile');
Route::get('/loyalty', function () {
    return redirect()->route('customer.profile');
});
Route::get('/loyalty/profile', function () {
    return redirect()->route('customer.profile');
});

// Payment Gateway Callback / Popup Auto-Close
Route::get('/payment/mayar/return', [\App\Http\Controllers\PaymentReturnController::class, 'handle'])->name('payment.mayar.return');

// Mayar Webhook & Callback Aliases (accepts GET/POST on web paths)
Route::match(['get', 'post'], '/payment/mayar/webhook/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);
Route::match(['get', 'post'], '/payment/mayar/callback/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);
Route::match(['get', 'post'], '/api/payment/mayar/webhook/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);

// 5. Store Frontend Landing Page (base_url/{nama_toko})
// Menampilkan seluruh data yang terikat ke toko tersebut
Route::get('/{store_slug}', [StoreFrontController::class, 'show'])
    ->name('store.front')
    ->where('store_slug', '^(?!superadmin|backend|admin|api|campaign|po|trace|stores|storage|build|favicon|customer|payment|loyalty).*$');
