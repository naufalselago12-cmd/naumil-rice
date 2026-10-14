<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Models\Order;
use App\Models\Menu;
use App\Models\Campaign;
use Illuminate\Http\Request;

class SuperAdminDashboardController extends Controller
{
    public function index()
    {
        $totalStores = Store::count();
        $activeStores = Store::where('status', 'active')->count();
        $totalUsers = User::count();
        $totalStoreAdmins = User::where('role', User::ROLE_STORE_ADMIN)->count();
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', '!=', Order::STATUS_REJECT)->sum('total_amount');
        $totalMenus = Menu::count();
        $totalCampaigns = Campaign::where('is_active', true)->count();

        // Stores with details & assigned users
        $stores = Store::with(['users' => function ($q) {
            $q->where('role', User::ROLE_STORE_ADMIN);
        }])
        ->withCount(['menus', 'categories', 'campaigns', 'orders'])
        ->get();

        // Recent orders across all stores
        $recentOrders = Order::with(['store', 'client'])
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Recent users
        $recentUsers = User::with('store')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'totalStores',
            'activeStores',
            'totalUsers',
            'totalStoreAdmins',
            'totalOrders',
            'totalRevenue',
            'totalMenus',
            'totalCampaigns',
            'stores',
            'recentOrders',
            'recentUsers'
        ));
    }

    public function switchToStore(Store $store)
    {
        $superadmin = \Illuminate\Support\Facades\Auth::guard('superadmin')->user() 
            ?? \Illuminate\Support\Facades\Auth::guard('web')->user();

        if ($superadmin) {
            \Illuminate\Support\Facades\Auth::guard('web')->login($superadmin);
        }

        return redirect()->to(url('/' . ($store->slug ?: $store->id) . '/backend'))
            ->with('info', "Beralih ke backend cabang: {$store->name} [Super Admin Access]");
    }
}
